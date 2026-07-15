@extends('errors.layout')

@section('code', '419')
@section('title', 'Sesi Telah Berakhir')
@section('desc', 'Halaman ini kedaluwarsa karena sesi Anda sudah berakhir — biasanya setelah lama tidak ada aktivitas. Silakan masuk kembali; data yang belum tersimpan perlu diisi ulang.')

@section('actions')
  <a class="btn btn-pri" href="{{ route('login') }}">Masuk Kembali</a>
@endsection
