
const catalog = [
    {id:1,name:"Áo thun basic cotton",cat:"ao",sub:"Áo thun ngắn tay",gender:"nam",price:159000,old:199000,img:"photo-1521572163474-6864f9cf17ab",sale:true},
    {id:2,name:"Áo thun oversize",cat:"ao",sub:"Áo thun ngắn tay",gender:"unisex",price:189000,old:229000,img:"photo-1503341504253-dff4815485f1",sale:true},
    {id:3,name:"Áo sơ mi trắng",cat:"ao",sub:"Áo sơ mi",gender:"nam",price:299000,old:0,img:"photo-1598033129183-c4f50c736f10",sale:false},
    {id:4,name:"Áo sơ mi nữ thanh lịch",cat:"ao",sub:"Áo sơ mi",gender:"nu",price:279000,old:329000,img:"photo-1598554747436-c9293d6a588f",sale:true},
    {id:5,name:"Áo polo cổ bẻ",cat:"ao",sub:"Áo polo",gender:"nam",price:259000,old:0,img:"photo-1625910513413-5fc45e7d2d72",sale:false},
    {id:6,name:"Áo len cổ tròn",cat:"ao",sub:"Áo len",gender:"nu",price:359000,old:399000,img:"photo-1576566588028-4147f3842f27",sale:true},
    {id:7,name:"Áo hoodie basic",cat:"ao",sub:"Áo hoodie",gender:"unisex",price:399000,old:0,img:"photo-1556821840-3a63f95609a7",sale:false},
    {id:8,name:"Áo khoác denim",cat:"ao",sub:"Áo khoác",gender:"nam",price:599000,old:699000,img:"photo-1551028719-00167b16eac5",sale:true},

    {id:9,name:"Quần jeans xanh",cat:"quan",sub:"Quần jeans",gender:"nam",price:399000,old:0,img:"photo-1542272604-787c3835535d",sale:false},
    {id:10,name:"Quần jeans nữ ống rộng",cat:"quan",sub:"Quần jeans",gender:"nu",price:429000,old:499000,img:"photo-1541099649105-f69ad21f3246",sale:true},
    {id:11,name:"Quần tây công sở",cat:"quan",sub:"Quần tây",gender:"nam",price:459000,old:0,img:"photo-1473966968600-fa801b869a1a",sale:false},
    {id:12,name:"Quần short năng động",cat:"quan",sub:"Quần short",gender:"nam",price:199000,old:249000,img:"photo-1591195853828-11db59a44f6b",sale:true},
    {id:13,name:"Quần jogger thể thao",cat:"quan",sub:"Quần jogger",gender:"unisex",price:289000,old:0,img:"photo-1552902865-b72c031ac5ea",sale:false},
    {id:14,name:"Quần ống rộng nữ",cat:"quan",sub:"Quần ống rộng",gender:"nu",price:349000,old:0,img:"photo-1509551388413-e18d0ac5d495",sale:false},

    {id:15,name:"Váy midi thanh lịch",cat:"vay",sub:"Váy midi",gender:"nu",price:459000,old:529000,img:"photo-1595777457583-95e059d581b8",sale:true},
    {id:16,name:"Váy hoa mùa hè",cat:"vay",sub:"Váy hoa",gender:"nu",price:389000,old:0,img:"photo-1496747611176-843222e1e57c",sale:false},
    {id:17,name:"Váy dự tiệc",cat:"vay",sub:"Váy dự tiệc",gender:"nu",price:799000,old:899000,img:"photo-1566174053879-31528523f8ae",sale:true},

    {id:18,name:"Sneaker trắng cổ thấp",cat:"giay",sub:"Sneaker",gender:"unisex",price:699000,old:799000,img:"photo-1542291026-7eec264c27ff",sale:true},
    {id:19,name:"Giày chạy bộ thể thao",cat:"giay",sub:"Giày chạy bộ",gender:"nam",price:899000,old:0,img:"photo-1542291026-7eec264c27ff",sale:false},
    {id:20,name:"Giày loafer cổ điển",cat:"giay",sub:"Giày loafer",gender:"nam",price:799000,old:0,img:"photo-1614252369475-531eba835eb1",sale:false},
    {id:21,name:"Giày cao gót nữ",cat:"giay",sub:"Giày cao gót",gender:"nu",price:649000,old:749000,img:"photo-1543163521-1bf539c55dd2",sale:true},
    {id:22,name:"Sneaker phong cách",cat:"giay",sub:"Sneaker",gender:"nu",price:749000,old:0,img:"photo-1460353581641-37baddab0fa2",sale:false},

    {id:23,name:"Dép quai ngang",cat:"dep",sub:"Dép quai ngang",gender:"unisex",price:129000,old:159000,img:"photo-1603487742131-4160ec999306",sale:true},
    {id:24,name:"Sandal nữ mùa hè",cat:"dep",sub:"Sandal",gender:"nu",price:299000,old:0,img:"photo-1562273138-f46be4ebdf33",sale:false},
    {id:25,name:"Dép đi trong nhà",cat:"dep",sub:"Dép trong nhà",gender:"unisex",price:99000,old:0,img:"photo-1543163521-1bf539c55dd2",sale:false},

    {id:26,name:"Túi xách thời trang",cat:"phukien",sub:"Túi xách",gender:"nu",price:599000,old:699000,img:"photo-1584917865442-de89df76afd3",sale:true},
    {id:27,name:"Balo đi học",cat:"phukien",sub:"Balo",gender:"unisex",price:429000,old:0,img:"photo-1553062407-98eeb64c6a62",sale:false},
    {id:28,name:"Túi đeo chéo",cat:"phukien",sub:"Túi đeo chéo",gender:"unisex",price:329000,old:0,img:"photo-1548036328-c9fa89d128fa",sale:false},
    {id:29,name:"Kính mát thời trang",cat:"phukien",sub:"Kính mát",gender:"unisex",price:199000,old:249000,img:"photo-1511499767150-a48a237f0083",sale:true},
    {id:30,name:"Mũ lưỡi trai",cat:"phukien",sub:"Mũ",gender:"unisex",price:159000,old:0,img:"photo-1588850561407-ed78c282e89b",sale:false},
    {id:31,name:"Thắt lưng da",cat:"phukien",sub:"Thắt lưng",gender:"nam",price:249000,old:0,img:"photo-1624222247344-550fb8b2b7f7",sale:false},
    {id:32,name:"Ví da tối giản",cat:"phukien",sub:"Ví",gender:"unisex",price:289000,old:349000,img:"photo-1627123424574-724758594e93",sale:true}
];

const subcategoryMap = {
    ao: ["Áo thun ngắn tay", "Áo sơ mi", "Áo polo", "Áo len", "Áo hoodie", "Áo khoác"],
    quan: ["Quần jeans", "Quần tây", "Quần short", "Quần jogger", "Quần ống rộng"],
    vay: ["Váy midi", "Váy hoa", "Váy dự tiệc"],
    giay: ["Sneaker", "Giày chạy bộ", "Giày loafer", "Giày cao gót"],
    dep: ["Dép quai ngang", "Sandal", "Dép trong nhà"],
    phukien: ["Túi xách", "Balo", "Túi đeo chéo", "Kính mát", "Mũ", "Thắt lưng", "Ví"]
};

const state = {
    category: "all",
    subcategory: "all",
    search: "",
    gender: "all",
    price: "all",
    sort: "default"
};

const productGrid = document.getElementById("productGrid");
const resultCount = document.getElementById("resultCount");
const emptyMessage = document.getElementById("emptyMessage");
const subcategories = document.getElementById("subcategories");
const searchInput = document.getElementById("searchInput");
const genderFilter = document.getElementById("genderFilter");
const priceFilter = document.getElementById("priceFilter");
const sortFilter = document.getElementById("sortFilter");
const cartCountElement = document.getElementById("cartCount");

const CART_STORAGE_KEY = "luxeWearCartV1";
let cart = {};
try {
    cart = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || "{}") || {};
} catch {
    cart = {};
}
const favorites = new Set();

function formatPrice(price) {
    return price.toLocaleString("vi-VN") + "đ";
}

function getCategoryName(category) {
    const names = {
        ao: "Áo",
        quan: "Quần",
        vay: "Váy",
        giay: "Giày",
        dep: "Dép",
        phukien: "Phụ kiện"
    };
    return names[category] || "Thời trang";
}

function renderSubcategories() {
    subcategories.innerHTML = "";

    const list = subcategoryMap[state.category];
    if (!list) return;

    const allButton = document.createElement("button");
    allButton.textContent = "Tất cả loại";
    allButton.classList.toggle("active", state.subcategory === "all");
    allButton.addEventListener("click", () => {
        state.subcategory = "all";
        renderSubcategories();
        renderProducts();
    });
    subcategories.appendChild(allButton);

    list.forEach(name => {
        const button = document.createElement("button");
        button.textContent = name;
        button.classList.toggle("active", state.subcategory === name);

        button.addEventListener("click", () => {
            state.subcategory = name;
            renderSubcategories();
            renderProducts();
        });

        subcategories.appendChild(button);
    });
}

function matchesProduct(product) {
    let matchesCategory = true;

    if (state.category === "nam") {
        matchesCategory = product.gender === "nam" || product.gender === "unisex";
    } else if (state.category === "nu") {
        matchesCategory = product.gender === "nu" || product.gender === "unisex";
    } else if (state.category === "sale") {
        matchesCategory = product.sale;
    } else if (state.category !== "all") {
        matchesCategory = product.cat === state.category;
    }

    if (!matchesCategory) return false;

    if (state.subcategory !== "all" && product.sub !== state.subcategory) {
        return false;
    }

    if (state.gender !== "all" && product.gender !== state.gender) {
        return false;
    }

    if (state.search) {
        const keyword = state.search.toLocaleLowerCase("vi");
        const searchable = `${product.name} ${product.cat} ${product.sub} ${product.gender}`.toLocaleLowerCase("vi");
        if (!searchable.includes(keyword)) return false;
    }

    if (state.price === "1" && product.price >= 200000) return false;
    if (state.price === "2" && (product.price < 200000 || product.price > 500000)) return false;
    if (state.price === "3" && (product.price <= 500000 || product.price > 1000000)) return false;
    if (state.price === "4" && product.price <= 1000000) return false;

    return true;
}

function createProductCard(product) {
    const card = document.createElement("article");
    card.className = "product";

    const imageBox = document.createElement("div");
    imageBox.className = "product-image";

    const img = document.createElement("img");
    img.src = `https://images.unsplash.com/${product.img}?auto=format&fit=crop&w=700&q=80`;
    img.alt = product.name;
    img.loading = "lazy";
    img.onerror = () => {
        img.onerror = null;
        img.src = "https://placehold.co/600x700/f2eee8/333?text=LUXE+WEAR";
    };

    imageBox.appendChild(img);

    if (product.sale) {
        const badge = document.createElement("span");
        badge.className = "badge";
        badge.textContent = "SALE";
        imageBox.appendChild(badge);
    }

    const heart = document.createElement("button");
    heart.className = "heart" + (favorites.has(product.id) ? " liked" : "");
    heart.type = "button";
    heart.textContent = favorites.has(product.id) ? "♥" : "♡";
    heart.setAttribute("aria-label", "Yêu thích sản phẩm");

    heart.addEventListener("click", () => {
        if (favorites.has(product.id)) {
            favorites.delete(product.id);
            heart.classList.remove("liked");
            heart.textContent = "♡";
        } else {
            favorites.add(product.id);
            heart.classList.add("liked");
            heart.textContent = "♥";
        }
    });

    imageBox.appendChild(heart);

    const info = document.createElement("div");
    info.className = "product-info";

    const category = document.createElement("div");
    category.className = "product-category";
    category.textContent = getCategoryName(product.cat) + " · " + product.sub;

    const name = document.createElement("h3");
    name.className = "product-name";
    name.textContent = product.name;

    const price = document.createElement("div");
    price.className = "price";
    price.textContent = formatPrice(product.price);

    if (product.old > product.price) {
        const oldPrice = document.createElement("span");
        oldPrice.className = "old-price";
        oldPrice.textContent = formatPrice(product.old);
        price.appendChild(oldPrice);
    }

    const detailButton = document.createElement("button");
    detailButton.className = "detail-btn";
    detailButton.type = "button";
    detailButton.textContent = "XEM SẢN PHẨM";
    detailButton.addEventListener("click", () => openProductDetail(product));

    const addButton = document.createElement("button");
    addButton.className = "add-btn";
    addButton.type = "button";
    addButton.textContent = "THÊM VÀO GIỎ +";
    addButton.addEventListener("click", () => {
        addToCart(product, "M");
        addButton.textContent = "ĐÃ THÊM ✓";
        setTimeout(() => { addButton.textContent = "THÊM VÀO GIỎ +"; }, 900);
    });

    info.append(category, name, price, detailButton, addButton);
    card.append(imageBox, info);

    return card;
}

const productModal = document.getElementById("productModal");
const productDetailContent = document.getElementById("productDetailContent");
const cartDrawer = document.getElementById("cartDrawer");
const cartBackdrop = document.getElementById("cartBackdrop");
const cartItemsElement = document.getElementById("cartItems");
const cartSubtotalElement = document.getElementById("cartSubtotal");
const cartToast = document.getElementById("cartToast");
let toastTimer;

function cartKey(productId, size) { return `${productId}-${size}`; }
function cartQuantity() { return Object.values(cart).reduce((sum, item) => sum + item.quantity, 0); }
function cartTotal() { return Object.values(cart).reduce((sum, item) => sum + item.product.price * item.quantity, 0); }
function saveCart() {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
    cartCountElement.textContent = cartQuantity();
    renderCart();
}
function showToast(message) {
    cartToast.textContent = message;
    cartToast.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => cartToast.classList.remove("show"), 2200);
}
function addToCart(product, size = "M") {
    window.LuxeTracker?.track("add_to_cart", { product_id: product.id, product_name: product.name, size: size });
    const key = cartKey(product.id, size);
    if (cart[key]) cart[key].quantity += 1;
    else cart[key] = { product, size, quantity: 1 };
    saveCart();
    showToast(`Đã thêm "${product.name}" vào giỏ hàng`);
}
function openCart() {
    window.LuxeTracker?.track("cart_open", { item_count: cartQuantity() });
    cartDrawer.classList.add("open");
    cartBackdrop.classList.add("open");
    cartDrawer.setAttribute("aria-hidden", "false");
    cartBackdrop.setAttribute("aria-hidden", "false");
    document.body.classList.add("no-scroll");
    renderCart();
}
function closeCart() {
    cartDrawer.classList.remove("open");
    cartBackdrop.classList.remove("open");
    cartDrawer.setAttribute("aria-hidden", "true");
    cartBackdrop.setAttribute("aria-hidden", "true");
    document.body.classList.remove("no-scroll");
}
function renderCart() {
    if (!cartItemsElement) return;
    cartItemsElement.innerHTML = "";
    const entries = Object.entries(cart);
    if (!entries.length) {
        cartItemsElement.innerHTML = `<div class="cart-empty"><span>♡</span><h3>Giỏ hàng đang trống</h3><p>Chọn vài món đồ bạn yêu thích nhé.</p><button type="button" id="emptyShopBtn">KHÁM PHÁ SẢN PHẨM</button></div>`;
        document.getElementById("emptyShopBtn")?.addEventListener("click", () => {
            closeCart();
            document.getElementById("products").scrollIntoView({ behavior: "smooth" });
        });
    } else {
        entries.forEach(([key, item]) => {
            const row = document.createElement("article");
            row.className = "cart-item";
            const image = `https://images.unsplash.com/${item.product.img}?auto=format&fit=crop&w=240&q=75`;
            row.innerHTML = `<img src="${image}" alt=""><div class="cart-item-info"><h3></h3><p class="cart-item-meta"></p><strong class="cart-item-price"></strong><div class="quantity-control"><button type="button" data-action="minus" aria-label="Giảm số lượng">−</button><span>${item.quantity}</span><button type="button" data-action="plus" aria-label="Tăng số lượng">+</button><button type="button" class="remove-item" data-action="remove">Xóa</button></div></div>`;
            row.querySelector("h3").textContent = item.product.name;
            row.querySelector(".cart-item-meta").textContent = `Phân loại: ${item.size}`;
            row.querySelector(".cart-item-price").textContent = formatPrice(item.product.price * item.quantity);
            row.querySelector('[data-action="minus"]').addEventListener("click", () => {
                item.quantity -= 1;
                if (item.quantity <= 0) delete cart[key];
                saveCart();
            });
            row.querySelector('[data-action="plus"]').addEventListener("click", () => { item.quantity += 1; saveCart(); });
            row.querySelector('[data-action="remove"]').addEventListener("click", () => {
                window.LuxeTracker?.track("remove_from_cart", { product_id: item.product.id, product_name: item.product.name, size: item.size });
                delete cart[key]; saveCart(); showToast("Đã xóa sản phẩm khỏi giỏ hàng");
            });
            cartItemsElement.appendChild(row);
        });
    }
    cartSubtotalElement.textContent = formatPrice(cartTotal());
    cartCountElement.textContent = cartQuantity();
}
function openProductDetail(product) {
    window.LuxeTracker?.track("product_detail", { product_id: product.id, product_name: product.name });
    const isFootwear = product.cat === "giay" || product.cat === "dep";
    const isAccessory = product.cat === "phukien";
    const sizes = isAccessory ? ["Freesize"] : isFootwear ? ["36","37","38","39","40","41","42","43"] : ["S","M","L","XL"];
    const image = `https://images.unsplash.com/${product.img}?auto=format&fit=crop&w=900&q=85`;
    productDetailContent.innerHTML = `<div class="detail-layout"><div class="detail-image-wrap"><img src="${image}" alt=""></div><div class="detail-copy"><span class="detail-kicker">LUXE WEAR / ${getCategoryName(product.cat).toUpperCase()}</span><h2 id="detailName"></h2><div class="detail-price"></div><p class="detail-description">Thiết kế hiện đại, dễ phối đồ và phù hợp để thể hiện phong cách riêng mỗi ngày.</p><div class="detail-label">Kích cỡ / phân loại</div><div class="size-options">${sizes.map((size,index)=>`<button type="button" class="${index===0?"selected":""}" data-size="${size}">${size}</button>`).join("")}</div><div class="detail-label">Số lượng</div><div class="detail-quantity"><button type="button" id="detailMinus">−</button><span id="detailQty">1</span><button type="button" id="detailPlus">+</button></div><button type="button" class="detail-add-btn" id="detailAddBtn">THÊM VÀO GIỎ HÀNG — ${formatPrice(product.price)}</button><p class="detail-note">Hình ảnh mang tính minh họa cho website mẫu.</p></div></div>`;
    productDetailContent.querySelector("img").alt = product.name;
    productDetailContent.querySelector("#detailName").textContent = product.name;
    const priceElement = productDetailContent.querySelector(".detail-price");
    priceElement.textContent = formatPrice(product.price);
    if (product.old > product.price) {
        const old = document.createElement("span"); old.className = "old-price"; old.textContent = formatPrice(product.old); priceElement.appendChild(old);
    }
    let selectedSize = sizes[0], quantity = 1;
    const qtyText = productDetailContent.querySelector("#detailQty");
    productDetailContent.querySelectorAll("[data-size]").forEach(button => button.addEventListener("click", () => {
        selectedSize = button.dataset.size;
        productDetailContent.querySelectorAll("[data-size]").forEach(b => b.classList.toggle("selected", b === button));
    }));
    productDetailContent.querySelector("#detailMinus").addEventListener("click", () => { quantity = Math.max(1, quantity-1); qtyText.textContent = quantity; });
    productDetailContent.querySelector("#detailPlus").addEventListener("click", () => { quantity++; qtyText.textContent = quantity; });
    productDetailContent.querySelector("#detailAddBtn").addEventListener("click", () => {
        for (let i=0; i<quantity; i++) addToCart(product, selectedSize);
        productModal.classList.remove("open"); productModal.setAttribute("aria-hidden","true");
        document.body.classList.remove("no-scroll"); openCart();
    });
    productModal.classList.add("open"); productModal.setAttribute("aria-hidden","false"); document.body.classList.add("no-scroll");
}
document.getElementById("cartToggle").addEventListener("click", event => { event.preventDefault(); openCart(); });
document.getElementById("closeCart").addEventListener("click", closeCart);
document.getElementById("continueShopping").addEventListener("click", closeCart);
cartBackdrop.addEventListener("click", closeCart);
document.getElementById("closeProductModal").addEventListener("click", () => {
    productModal.classList.remove("open"); productModal.setAttribute("aria-hidden","true"); document.body.classList.remove("no-scroll");
});
productModal.addEventListener("click", event => {
    if (event.target === productModal) { productModal.classList.remove("open"); productModal.setAttribute("aria-hidden","true"); document.body.classList.remove("no-scroll"); }
});
document.addEventListener("keydown", event => {
    if (event.key === "Escape") { closeCart(); productModal.classList.remove("open"); productModal.setAttribute("aria-hidden","true"); document.body.classList.remove("no-scroll"); }
});
document.getElementById("checkoutBtn").addEventListener("click", () => {
    window.LuxeTracker?.track("checkout_click", { item_count: cartQuantity(), cart_total: cartTotal() });
    if (!cartQuantity()) { showToast("Giỏ hàng đang trống"); return; }
    alert("Đây là website mẫu. Bước thanh toán có thể được phát triển tiếp bằng form đặt hàng và PHP.");
});

function renderProducts() {
    let filtered = catalog.filter(matchesProduct);

    if (state.sort === "low") {
        filtered.sort((a, b) => a.price - b.price);
    } else if (state.sort === "high") {
        filtered.sort((a, b) => b.price - a.price);
    } else if (state.sort === "name") {
        filtered.sort((a, b) => a.name.localeCompare(b.name, "vi"));
    }

    productGrid.innerHTML = "";

    filtered.forEach(product => {
        productGrid.appendChild(createProductCard(product));
    });

    resultCount.textContent = `${filtered.length} sản phẩm`;
    emptyMessage.hidden = filtered.length > 0;
    productGrid.hidden = filtered.length === 0;

    document.querySelectorAll("[data-category]").forEach(button => {
        button.classList.toggle(
            "active",
            button.dataset.category === state.category &&
            button.classList.contains("filter-btn")
        );
    });
}

function setCategory(category) {
    window.LuxeTracker?.track("category_filter", { category: category });
    state.category = category;
    state.subcategory = "all";

    document.querySelectorAll(".filter-btn").forEach(button => {
        button.classList.toggle("active", button.dataset.category === category);
    });

    renderSubcategories();
    renderProducts();
}

document.querySelectorAll("[data-category]").forEach(link => {
    link.addEventListener("click", event => {
        event.preventDefault();
        setCategory(link.dataset.category);

        document.getElementById("products").scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    });
});

searchInput.addEventListener("input", () => {
    state.search = searchInput.value.trim();
    renderProducts();
});

document.getElementById("searchForm").addEventListener("submit", event => {
    event.preventDefault();
    state.search = searchInput.value.trim();
    window.LuxeTracker?.track("search", { query_length: state.search.length, has_query: Boolean(state.search) });
    renderProducts();

    document.getElementById("products").scrollIntoView({
        behavior: "smooth"
    });
});

genderFilter.addEventListener("change", () => {
    state.gender = genderFilter.value;
    window.LuxeTracker?.track("gender_filter", { value: state.gender });
    renderProducts();
});

priceFilter.addEventListener("change", () => {
    state.price = priceFilter.value;
    window.LuxeTracker?.track("price_filter", { value: state.price });
    renderProducts();
});

sortFilter.addEventListener("change", () => {
    state.sort = sortFilter.value;
    window.LuxeTracker?.track("sort_products", { value: state.sort });
    renderProducts();
});

document.getElementById("resetFilters").addEventListener("click", () => {
    window.LuxeTracker?.track("reset_filters");
    state.category = "all";
    state.subcategory = "all";
    state.search = "";
    state.gender = "all";
    state.price = "all";
    state.sort = "default";

    searchInput.value = "";
    genderFilter.value = "all";
    priceFilter.value = "all";
    sortFilter.value = "default";

    setCategory("all");
});

document.getElementById("newsletterForm").addEventListener("submit", event => {
    event.preventDefault();
    window.LuxeTracker?.track("newsletter_submit");
    alert("Cảm ơn bạn đã đăng ký nhận tin từ LUXE WEAR!");
    event.target.reset();
});

renderSubcategories();
renderProducts();
saveCart();
