#!/usr/bin/env bash
# lab.sh – các lệnh kiểm tra, khởi tạo và chạy dự án WebInsight trên Ubuntu/WSL.
# Cách dùng: ./lab.sh <lệnh>      (xem danh sách: ./lab.sh help)

# Luôn chạy trong thư mục chứa lab.sh, kể cả khi đường dẫn có dấu cách
cd "$(dirname "${BASH_SOURCE[0]}")" || exit 1

HOST="${HOST:-localhost}"
PORT="${PORT:-8000}"

need_php() {
  if ! command -v php >/dev/null 2>&1; then
    echo "Chưa cài PHP. Chạy: ./lab.sh install"
    exit 1
  fi
}

case "${1:-help}" in
  install)   # Cài PHP và extension cần thiết
    sudo apt update
    sudo apt install -y php-cli php-sqlite3 zip unzip curl
    php -v
    ;;
  check)     # Kiểm tra môi trường
    need_php
    php scripts/check_env.php
    ;;
  lint)      # Kiểm tra cú pháp tất cả file PHP (và JavaScript nếu có node)
    need_php
    fail=0
    while IFS= read -r -d '' f; do
      out=$(php -l "$f" 2>&1) || { echo "$out"; fail=1; }
    done < <(find . -name '*.php' -not -path './.git/*' -print0)
    if [ "$fail" -eq 0 ]; then
      echo "[OK] Tất cả file PHP đều đúng cú pháp ($(find . -name '*.php' -not -path './.git/*' | wc -l) file)."
    else
      echo "[LỖI] Có file PHP sai cú pháp (xem phía trên)."; exit 1
    fi
    if command -v node >/dev/null 2>&1; then
      node --check public/assets/tracker.js && node --check public/assets/app.js && echo "[OK] JavaScript đúng cú pháp."
    fi
    ;;
  init)      # Tạo CSDL + tài khoản quản trị + website demo
    need_php
    php scripts/init_db.php
    ;;
  admin)     # Tạo / đặt lại tài khoản: ./lab.sh admin <tên> <mật_khẩu>
    need_php
    php scripts/create_admin.php "$2" "$3"
    ;;
  run)       # Khởi động máy chủ PHP (Ctrl+C để dừng)
    need_php
    echo "WebInsight chạy tại http://$HOST:$PORT  (tài khoản mặc định: admin / Admin@123)"
    echo "Trang demo: http://$HOST:$PORT/demo.php"
    php -S "$HOST:$PORT" -t public
    ;;
  open)      # Mở trình duyệt (WSL: dùng trình duyệt Windows)
    url="http://$HOST:$PORT/"
    if command -v wslview >/dev/null 2>&1; then wslview "$url"
    elif command -v xdg-open >/dev/null 2>&1; then xdg-open "$url"
    elif command -v explorer.exe >/dev/null 2>&1; then explorer.exe "$url"
    else echo "Hãy tự mở: $url"; fi
    ;;
  seed)      # Tạo dữ liệu kiểm thử: ./lab.sh seed [id_website] [số_phiên]
    need_php
    php scripts/seed_test.php "$2" "$3"
    ;;
  test)      # Chạy kịch bản kiểm thử tự động
    need_php
    php scripts/run_tests.php
    ;;
  status)    # Xem dữ liệu đã ghi vào CSDL
    need_php
    php scripts/status.php
    ;;
  purge)     # Xóa dữ liệu quá thời hạn lưu trữ: ./lab.sh purge [số_ngày]
    need_php
    php scripts/purge.php "$2"
    ;;
  reset)     # Xóa CSDL để làm lại từ đầu
    read -r -p "Xóa toàn bộ CSDL (data/webinsight.sqlite)? Gõ 'yes' để xác nhận: " ans
    if [ "$ans" = "yes" ]; then rm -f data/webinsight.sqlite data/webinsight.sqlite-journal; echo "Đã xóa. Chạy ./lab.sh init để tạo lại."; fi
    ;;
  zip)       # Đóng gói dự án
    zip -r ../WebInsight_DeTai17.zip . -x 'data/*.sqlite*' 'logs/*.log' '.git/*' >/dev/null && echo "Đã tạo ../WebInsight_DeTai17.zip"
    ;;
  *)
    cat <<'HELP'
Lệnh của lab.sh:
  install   Cài PHP + extension (sudo apt)
  check     Kiểm tra môi trường
  lint      Kiểm tra cú pháp PHP/JS
  init      Tạo CSDL, tài khoản admin, website demo
  admin     Tạo/đặt lại tài khoản:  ./lab.sh admin <tên> <mật_khẩu>
  run       Chạy máy chủ PHP (HOST/PORT đổi được: PORT=9000 ./lab.sh run)
  open      Mở trình duyệt
  seed      Tạo dữ liệu kiểm thử:   ./lab.sh seed [id_website] [số_phiên]
  test      Chạy kịch bản kiểm thử tự động
  status    Xem dữ liệu trong CSDL
  purge     Xóa dữ liệu quá hạn:    ./lab.sh purge [số_ngày]
  reset     Xóa CSDL làm lại
  zip       Đóng gói dự án
HELP
    ;;
esac
