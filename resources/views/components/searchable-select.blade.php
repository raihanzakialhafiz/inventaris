@props([
    'name',
    'options'           => [],      // [value => label] atau Collection model
    'selected'          => null,
    'placeholder'       => '— Pilih —',
    'searchPlaceholder' => 'Cari…',
    'required'          => false,
    'valueKey'          => 'id',
    'labelKey'          => 'name',
    'submitOnChange'    => false,
])

@php
    // Normalisasi opsi ke bentuk [['value'=>..,'label'=>..], ...]
    $normalized = collect($options)->map(function ($label, $key) use ($valueKey, $labelKey) {
        if (is_object($label) || is_array($label)) {
            $row = (object) $label;
            return ['value' => (string) data_get($row, $valueKey), 'label' => (string) data_get($row, $labelKey)];
        }
        return ['value' => (string) $key, 'label' => (string) $label];
    })->values()->all();

    $selectedStr = $selected === null ? '' : (string) $selected;
@endphp

<div class="ss" x-data="{
        open: false,
        q: '',
        value: @js($selectedStr),
        options: @js($normalized),
        panelStyle: '',
        get label() {
            const o = this.options.find(o => String(o.value) === String(this.value));
            return o ? o.label : '';
        },
        get filtered() {
            if (!this.q.trim()) return this.options;
            const s = this.q.toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(s));
        },
        toggle() { this.open ? this.close() : this.show(); },
        show() {
            this.open = true;
            this.$nextTick(() => { this.position(); this.$refs.q && this.$refs.q.focus(); });
        },
        close() { this.open = false; this.q = ''; },
        /* position:fixed relative to the button so the panel escapes any
           overflow:hidden/auto ancestor (modals, cards) — never clipped. */
        position() {
            const btn = this.$refs.btn.getBoundingClientRect();
            const panelH = this.$refs.panel.offsetHeight;
            const gap = 5;
            const below = window.innerHeight - btn.bottom;
            const top = (below < panelH + 12 && btn.top > panelH + 12)
                ? btn.top - panelH - gap          // flip up when no room below
                : btn.bottom + gap;
            this.panelStyle = `top:${top}px; left:${btn.left}px; width:${btn.width}px;`;
        },
        pick(v) {
            this.value = v;
            this.close();
            @if($submitOnChange) this.$nextTick(() => { const f = this.$root.closest('form'); if (f) f.submit(); }); @endif
        }
     }"
     x-modelable="value"
     @click.outside="close()"
     @keydown.escape.stop="close()"
     @scroll.window="open && position()"
     @resize.window="open && position()"
     {{ $attributes }}>

    <input type="hidden" name="{{ $name }}" :value="value" @if($required) required @endif>

    <button type="button" class="ss-btn" x-ref="btn" @click="toggle()" :class="{ 'ss-open': open }">
        <span x-text="label || @js($placeholder)" :class="{ 'ss-ph': !label }"></span>
        <span class="ss-chev" :class="{ 'flip': open }">▾</span>
    </button>

    {{-- Teleport ke <body> agar position:fixed relatif ke viewport (modal punya
         transform yang kalau tidak, membuat panel salah posisi) & tak terpotong. --}}
    <template x-teleport="body">
      <div class="ss-panel" x-ref="panel" x-show="open" x-cloak :style="panelStyle" x-transition.opacity.duration.120ms>
        <div class="ss-search-wrap">
            <input type="text" class="ss-search" x-model="q" x-ref="q"
                   placeholder="{{ $searchPlaceholder }}" autocomplete="off">
        </div>
        <div class="ss-opts">
            @unless($required)
                <div class="ss-opt" :class="{ 'sel': value === '' }" @click="pick('')">{{ $placeholder }}</div>
            @endunless
            <template x-for="o in filtered" :key="o.value">
                <div class="ss-opt" :class="{ 'sel': String(o.value) === String(value) }"
                     @click="pick(o.value)" x-text="o.label"></div>
            </template>
            <div class="ss-empty" x-show="filtered.length === 0">Tidak ada hasil</div>
        </div>
      </div>
    </template>
</div>
