<?php
declare(strict_types=1);

final class Analytics
{
    public function __construct(private PDO $db) {}

    public function ingest(array $data, string $apiKey): array
    {
        $website = $this->websiteByKey($apiKey);
        if (!$website) return ['status'=>401,'body'=>['error'=>'invalid_website_key']];
        $wid = (int)$website['id'];

        $required = ['event_id','session_id','event_name','page_path','occurred_at'];
        foreach ($required as $key) {
            if (!isset($data[$key]) || !is_string($data[$key]) || trim($data[$key]) === '') {
                $this->error($wid, 'invalid_field');
                return ['status'=>422,'body'=>['error'=>'invalid_field','field'=>$key]];
            }
        }
        if (strlen($data['event_id']) > 100 || strlen($data['session_id']) > 100 ||
            strlen($data['event_name']) > 80 || strlen($data['page_path']) > 250) {
            $this->error($wid, 'field_too_long');
            return ['status'=>422,'body'=>['error'=>'field_too_long']];
        }
        if (preg_match('/[\r\n]/', $data['page_path']) || !str_starts_with($data['page_path'], '/')) {
            $this->error($wid, 'invalid_page_path');
            return ['status'=>422,'body'=>['error'=>'invalid_page_path']];
        }
        try {
            $occurred = new DateTimeImmutable($data['occurred_at']);
        } catch (Throwable) {
            return ['status'=>422,'body'=>['error'=>'invalid_timestamp']];
        }
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $occurred = $occurred->setTimezone(new DateTimeZone('UTC'));
        if ($occurred > $now->modify('+5 minutes') || $occurred < $now->modify('-' . LATE_EVENT_HOURS . ' hours')) {
            $this->error($wid, 'timestamp_out_of_window');
            return ['status'=>422,'body'=>['error'=>'timestamp_out_of_window','late_window_hours'=>LATE_EVENT_HOURS]];
        }

        $def = $this->db->prepare('SELECT event_type FROM event_definitions WHERE website_id=? AND event_name=? AND enabled=1');
        $def->execute([$wid,$data['event_name']]);
        $type = $def->fetchColumn();
        if (!$type) return ['status'=>403,'body'=>['error'=>'event_not_allowed']];

        // Only allow the fields listed above; never accept arbitrary form contents.
        $canonical = [
            'event_id'=>$data['event_id'], 'session_id'=>$data['session_id'],
            'event_name'=>$data['event_name'], 'page_path'=>$data['page_path'],
            'occurred_at'=>$occurred->format('Y-m-d\TH:i:s.u\Z')
        ];
        $hash = hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES));
        $existing = $this->db->prepare('SELECT payload_hash FROM events WHERE website_id=? AND event_id=?');
        $existing->execute([$wid,$data['event_id']]);
        $oldHash = $existing->fetchColumn();
        if ($oldHash !== false) {
            if (hash_equals((string)$oldHash, $hash)) return ['status'=>200,'body'=>['status'=>'duplicate_ignored']];
            $this->error($wid, 'event_id_conflict');
            return ['status'=>409,'body'=>['error'=>'event_id_conflict']];
        }

        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare('INSERT INTO sessions(id,website_id,started_at,last_seen_at) VALUES(?,?,?,?) ON CONFLICT(website_id,id) DO NOTHING');
            $s->execute([$data['session_id'],$wid,$occurred->format('c'),$occurred->format('c')]);
            $check = $this->db->prepare('SELECT website_id FROM sessions WHERE id=? AND website_id=?');
            $check->execute([$data['session_id'],$wid]);
            if ((int)$check->fetchColumn() !== $wid) {
                $this->db->rollBack();
                return ['status'=>409,'body'=>['error'=>'session_website_conflict']];
            }
            $s = $this->db->prepare('INSERT INTO events(event_id,website_id,session_id,event_name,event_type,page_path,occurred_at,received_at,payload_hash) VALUES(?,?,?,?,?,?,?,?,?)');
            $s->execute([$data['event_id'],$wid,$data['session_id'],$data['event_name'],$type,$data['page_path'],$occurred->format('c'),$now->format('c'),$hash]);
            $s = $this->db->prepare('UPDATE sessions SET last_seen_at=? WHERE id=?');
            $s->execute([$now->format('c'),$data['session_id']]);
            $this->db->commit();
            return ['status'=>201,'body'=>['status'=>'created']];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->error($wid, 'storage_error');
            return ['status'=>500,'body'=>['error'=>'storage_error']];
        }
    }

    private function websiteByKey(string $key): ?array
    {
        $s = $this->db->prepare('SELECT * FROM websites WHERE api_key=?');
        $s->execute([$key]);
        $row = $s->fetch();
        return $row ?: null;
    }

    private function error(?int $wid, string $reason): void
    {
        // Bounded error log: at most the latest 500 records.
        $s = $this->db->prepare('INSERT INTO ingestion_errors(website_id,reason,created_at) VALUES(?,?,?)');
        $s->execute([$wid,$reason,gmdate('c')]);
        $this->db->exec('DELETE FROM ingestion_errors WHERE id NOT IN (SELECT id FROM ingestion_errors ORDER BY id DESC LIMIT 500)');
    }
}
