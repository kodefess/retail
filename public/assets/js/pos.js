/* ==========================================================================
   pos.js - kasir (penjualan) dan form pembelian
   Satu file untuk dua mode: window.POS.mode = 'sale' | 'purchase'
   Data produk dikirim dari PHP lewat window.POS.products (JSON).
   Keranjang disimpan di memori (Map) lalu dikirim ke server sebagai JSON saat Simpan.
   ========================================================================== */
(() => {
  const root = document.getElementById("pos");
  if (!root) return;

  const { mode, products } = window.POS;
  const isSale = mode === "sale";
  const rupiah = (n) =>
    "Rp " + new Intl.NumberFormat("id-ID").format(Math.round(n));
  const esc = (s) =>
    String(s).replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );
  // PENTING: cari di seluruh dokumen, bukan hanya di dalam #pos.
  // Tombol melayang (#cartFab, #fabCount, #fabTotal) letaknya di luar #pos.
  const $ = (s) => document.querySelector(s);

  const cart = new Map(); // id -> { id, name, unit, qty, price, stock }
  let category = "Semua",
    query = "";

  const grid = $("#productGrid"),
    lines = $("#cartLines"),
    search = $("#search");
  const discountEl = $("#discount"),
    methodEl = $("#method"),
    paidEl = $("#paid"),
    paidWrap = $("#paidWrap");
  const dueWrap = $("#dueWrap"),
    form = $("#posForm"),
    cartInput = $("#cartInput");

  // ---------- Daftar produk ----------
  function renderCategories() {
    const cats = ["Semua", ...new Set(products.map((p) => p.category))];
    $("#chips").innerHTML = cats
      .map(
        (c) =>
          `<button type="button" class="chip ${c === category ? "active" : ""}" data-cat="${esc(c)}">${esc(c)}</button>`,
      )
      .join("");
  }

  function visibleProducts() {
    const q = query.toLowerCase();
    return products.filter(
      (p) =>
        (category === "Semua" || p.category === category) &&
        (!q ||
          p.name.toLowerCase().includes(q) ||
          p.sku.toLowerCase().includes(q)),
    );
  }

  function renderProducts() {
    const list = visibleProducts();
    if (!list.length) {
      grid.innerHTML =
        '<div class="empty" style="grid-column:1/-1"><i class="bi bi-search"></i>Produk tidak ditemukan.</div>';
      return;
    }
    grid.innerHTML = list
      .map((p) => {
        const out = isSale && +p.stock <= 0;
        return `<button type="button" class="pos-item" data-id="${p.id}" ${out ? "disabled" : ""}>
        <span class="name">${esc(p.name)}</span>
        <span class="meta">${esc(p.sku)}</span>
        <span class="num fw-semibold mt-1">${rupiah(p.price)}</span>
        <span class="meta">${out ? "Stok habis" : "Stok " + p.stock + " " + esc(p.unit)}</span>
      </button>`;
      })
      .join("");
  }

  // ---------- Keranjang ----------
  function add(id) {
    const p = products.find((x) => +x.id === +id);
    if (!p) return;
    const line = cart.get(+id);
    if (line) {
      if (isSale && line.qty >= +p.stock) return; // jangan melebihi stok
      line.qty++;
    } else {
      cart.set(+id, {
        id: +p.id,
        name: p.name,
        unit: p.unit,
        qty: 1,
        price: +p.price,
        stock: +p.stock,
      });
    }
    renderCart();
  }

  function renderCart() {
    if (!cart.size) {
      lines.innerHTML =
        '<div class="empty"><i class="bi bi-cart"></i>Pilih produk untuk memulai.</div>';
    } else {
      lines.innerHTML = [...cart.values()]
        .map(
          (l) => `
        <div class="cart-line" data-id="${l.id}">
          <div class="d-flex justify-content-between gap-2">
            <div class="fw-semibold small">${esc(l.name)}</div>
            <button type="button" class="btn btn-sm p-0 text-secondary" data-remove="${l.id}" aria-label="Hapus"><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="qty">
              <button type="button" data-dec="${l.id}" aria-label="Kurangi">&minus;</button>
              <span class="num">${l.qty}</span>
              <button type="button" data-inc="${l.id}" aria-label="Tambah">+</button>
            </div>
            <input type="number" min="0" class="form-control price-input num" value="${l.price}" data-price="${l.id}" aria-label="Harga">
          </div>
          <div class="text-end small text-secondary mt-1">Subtotal <span class="num" data-sub="${l.id}">${rupiah(l.qty * l.price)}</span></div>
        </div>`,
        )
        .join("");
    }
    totals();
  }

  function subtotal() {
    return [...cart.values()].reduce((s, l) => s + l.qty * l.price, 0);
  }

  function totals() {
    const sub = subtotal();
    const disc = isSale
      ? Math.min(Math.max(+discountEl.value || 0, 0), sub)
      : 0;
    const total = sub - disc;
    $("#subtotal").textContent = rupiah(sub);
    $("#total").textContent = rupiah(total);
    const fabTotal = $("#fabTotal"),
      fabCount = $("#fabCount");
    if (fabTotal) fabTotal.textContent = rupiah(total);
    if (fabCount)
      fabCount.textContent = [...cart.values()].reduce((s, l) => s + l.qty, 0);

    const method = methodEl.value,
      credit = method === "credit";
    dueWrap.classList.toggle("d-none", !credit);
    // kolom "dibayar": untuk kredit = uang muka (DP); untuk tunai di kasir = uang diterima (hitung kembalian)
    const showPaid = credit || (isSale && method === "cash");
    paidWrap.classList.toggle("d-none", !showPaid);
    $("#paidLabel").textContent = credit ? "Uang muka (DP)" : "Uang diterima";

    const note = $("#paidNote");
    const paid = +paidEl.value || 0;
    if (credit)
      note.textContent =
        "Sisa jadi " +
        (isSale ? "piutang: " : "hutang: ") +
        rupiah(Math.max(total - paid, 0));
    else if (isSale && method === "cash" && paid > 0)
      note.textContent =
        paid >= total
          ? "Kembalian: " + rupiah(paid - total)
          : "Kurang: " + rupiah(total - paid);
    else note.textContent = "";

    $("#submitBtn").disabled = !cart.size;
  }

  // ---------- Event ----------
  grid.addEventListener("click", (e) => {
    const b = e.target.closest("[data-id]");
    if (b) add(b.dataset.id);
  });
  $("#chips").addEventListener("click", (e) => {
    const b = e.target.closest("[data-cat]");
    if (b) {
      category = b.dataset.cat;
      renderCategories();
      renderProducts();
    }
  });

  lines.addEventListener("click", (e) => {
    const inc = e.target.closest("[data-inc]"),
      dec = e.target.closest("[data-dec]"),
      rem = e.target.closest("[data-remove]");
    if (inc) {
      const l = cart.get(+inc.dataset.inc);
      if (!isSale || l.qty < l.stock) l.qty++;
    }
    if (dec) {
      const l = cart.get(+dec.dataset.dec);
      l.qty--;
      if (l.qty < 1) cart.delete(l.id);
    }
    if (rem) cart.delete(+rem.dataset.remove);
    if (inc || dec || rem) renderCart();
  });

  // ubah harga: perbarui tanpa render ulang supaya kursor tidak hilang
  lines.addEventListener("input", (e) => {
    const el = e.target.closest("[data-price]");
    if (!el) return;
    const l = cart.get(+el.dataset.price);
    l.price = Math.max(+el.value || 0, 0);
    lines.querySelector(`[data-sub="${l.id}"]`).textContent = rupiah(
      l.qty * l.price,
    );
    totals();
  });

  [discountEl, methodEl, paidEl].forEach(
    (el) => el && el.addEventListener("input", totals),
  );

  search.addEventListener("input", () => {
    query = search.value.trim();
    renderProducts();
  });
  // Enter di kolom cari: cocok persis SKU (scanner barcode) atau satu-satunya hasil -> langsung masuk keranjang
  search.addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    e.preventDefault();
    const list = visibleProducts();
    const exact = products.find(
      (p) => p.sku.toLowerCase() === query.toLowerCase(),
    );
    const pick = exact || (list.length === 1 ? list[0] : null);
    if (pick) {
      add(pick.id);
      search.value = "";
      query = "";
      renderProducts();
    }
  });
  // tekan "/" untuk fokus ke pencarian
  document.addEventListener("keydown", (e) => {
    if (
      e.key === "/" &&
      !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)
    ) {
      e.preventDefault();
      search.focus();
    }
  });

  form.addEventListener("submit", (e) => {
    if (!cart.size) {
      e.preventDefault();
      return;
    }
    cartInput.value = JSON.stringify(
      [...cart.values()].map((l) => ({ id: l.id, qty: l.qty, price: l.price })),
    );
  });

  $("#cartFab")?.addEventListener("click", () =>
    $("#cartCard").scrollIntoView({ behavior: "smooth" }),
  );

  renderCategories();
  renderProducts();
  renderCart();
})();
