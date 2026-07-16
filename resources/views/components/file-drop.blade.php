{{--
  Pemilih berkas: klik atau seret-lepas, dengan pratinjau gambar sebelum
  diunggah. Menggantikan <input type="file"> bawaan yang tidak bisa ditata
  dan tidak memberi umpan balik apa pun setelah berkas dipilih.

  Pakai: <x-file-drop name="logo" hint="PNG / JPG, maks 1 MB" />
--}}
@props([
    'name',
    'accept' => 'image/*',
    'hint'   => null,
])

<div x-data="{
       over: false,
       nama: '',
       ukuran: '',
       pratinjau: '',
       ambil(files) {
         if (! files || ! files.length) return;
         const f = files[0];
         // Berkas hasil seret-lepas tidak otomatis masuk input — pasang manual
         // agar ikut terkirim saat form disubmit.
         this.$refs.inp.files = files;
         this.nama = f.name;
         this.ukuran = f.size < 1048576
           ? Math.max(1, Math.round(f.size / 1024)) + ' KB'
           : (f.size / 1048576).toFixed(1) + ' MB';
         this.lepasPratinjau();
         this.pratinjau = f.type.startsWith('image/') ? URL.createObjectURL(f) : '';
       },
       batal() {
         this.$refs.inp.value = '';
         this.lepasPratinjau();
         this.nama = ''; this.ukuran = '';
       },
       lepasPratinjau() {
         if (this.pratinjau) URL.revokeObjectURL(this.pratinjau);
         this.pratinjau = '';
       },
     }"
     x-on:reset.window="batal()">

  <input type="file" name="{{ $name }}" accept="{{ $accept }}" x-ref="inp" class="fdrop-inp"
         @change="ambil($event.target.files)">

  <div class="fdrop" :class="{ 'over': over, 'has': nama }"
       @click="$refs.inp.click()"
       @dragover.prevent="over = true"
       @dragleave.prevent="over = false"
       @drop.prevent="over = false; ambil($event.dataTransfer.files)">

    <div class="fdrop-in" x-show="! nama">
      <span class="fdrop-ic"><x-icon name="send-up" width="18" height="18" /></span>
      <div class="fdrop-txt">
        <b>Klik untuk memilih</b> atau seret berkas ke sini
        @if($hint)<span>{{ $hint }}</span>@endif
      </div>
    </div>

    <div class="fdrop-in" x-show="nama" x-cloak>
      <img class="fdrop-thumb" :src="pratinjau" x-show="pratinjau" alt="">
      <div class="fdrop-txt">
        <b x-text="nama"></b>
        <span><span x-text="ukuran"></span> · siap diunggah, klik Simpan</span>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" @click.stop="batal()">✕ Batal</button>
    </div>
  </div>
</div>
