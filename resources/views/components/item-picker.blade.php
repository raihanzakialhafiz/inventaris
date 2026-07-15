@props([
    /** Nama variabel JS global berisi opsi barang, mis. "window.__permintaanItems". */
    'optionsVar',
])

{{--
  Dropdown barang searchable untuk baris form dinamis (dipakai di dalam
  <template x-for="(line, idx) in lines">). Mengandalkan scope Alpine `line`
  dan `idx` dari x-for, serta itemPicker() dari public/js/item-picker.js.
  Nama input mengikuti indeks baris: items[idx][item_id].
--}}
<div class="ss" x-data="itemPicker({{ $optionsVar }})"
     @click.outside="close()" @keydown.escape.stop="close()"
     @scroll.window="open && position()" @resize.window="open && position()">
  <input type="hidden" :name="'items['+idx+'][item_id]'" :value="line.item_id">
  <button type="button" class="ss-btn" x-ref="btn" @click="toggle()" :class="{ 'ss-open': open }">
    <span x-text="labelFor(line.item_id) || '— Pilih Barang —'" :class="{ 'ss-ph': !labelFor(line.item_id) }"></span>
    <span class="ss-chev" :class="{ flip: open }">▾</span>
  </button>
  <template x-teleport="body">
    <div class="ss-panel" x-ref="panel" x-show="open" x-cloak :style="panelStyle" x-transition.opacity.duration.120ms>
      <div class="ss-search-wrap">
        <input type="text" class="ss-search" x-model="q" x-ref="q" placeholder="Cari barang…" autocomplete="off">
      </div>
      <div class="ss-opts">
        <template x-for="o in filtered" :key="o.value">
          <div class="ss-opt" x-text="o.label"
               :class="{ 'sel': String(o.value) === String(line.item_id), 'ss-disabled': o.disabled }"
               @click="pick(o, line)"></div>
        </template>
        <div class="ss-empty" x-show="filtered.length === 0">Tidak ada hasil</div>
      </div>
    </div>
  </template>
</div>
