/**
 * Dropdown barang searchable (meniru komponen searchable-select) untuk baris
 * form dinamis (x-for). Dipakai lewat: x-data="itemPicker(window.__someItems)"
 * pada wrapper .ss; terikat ke scope `line.item_id` via labelFor()/pick().
 *
 * `options` = [{ value, label, disabled }].
 * Sengaja stateless terhadap baris (baca/tulis `line` dari scope, bukan closure)
 * agar aman saat baris dihapus (x-for :key="idx" memakai ulang DOM).
 */
function itemPicker(options) {
  return {
    open: false,
    q: '',
    panelStyle: '',
    options: options || [],

    labelFor(v) {
      const o = this.options.find((x) => String(x.value) === String(v));
      return o ? o.label : '';
    },
    get filtered() {
      const s = this.q.trim().toLowerCase();
      return s ? this.options.filter((o) => o.label.toLowerCase().includes(s)) : this.options;
    },

    toggle() { this.open ? this.close() : this.show(); },
    show() {
      this.open = true;
      this.$nextTick(() => { this.position(); this.$refs.q && this.$refs.q.focus(); });
    },
    close() { this.open = false; this.q = ''; },

    // position:fixed relatif tombol agar panel lepas dari overflow modal.
    position() {
      const b = this.$refs.btn.getBoundingClientRect();
      const h = this.$refs.panel.offsetHeight;
      const gap = 5;
      const below = window.innerHeight - b.bottom;
      const top = (below < h + 12 && b.top > h + 12) ? b.top - h - gap : b.bottom + gap;
      this.panelStyle = `top:${top}px; left:${b.left}px; width:${b.width}px;`;
    },

    pick(o, line) {
      if (o.disabled) return;
      line.item_id = o.value;
      this.close();
    },
  };
}
