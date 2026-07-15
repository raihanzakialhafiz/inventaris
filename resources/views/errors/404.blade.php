@extends('errors.layout')

@section('code', '404')
@section('title', 'Halaman Tidak Ditemukan')
@section('desc', 'Alamat yang Anda tuju tidak ada atau sudah dipindahkan. Periksa kembali tautan, atau kembali ke dasbor.')

@section('actions')
  <a class="btn btn-pri" href="{{ url('/dashboard') }}">Kembali ke Dasbor</a>
@endsection
