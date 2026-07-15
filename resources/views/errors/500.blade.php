@extends('errors.layout')

@section('code', '500')
@section('title', 'Terjadi Kesalahan Server')
@section('desc', 'Maaf, terjadi kesalahan pada sistem. Tim pengelola akan menanganinya — silakan coba lagi beberapa saat, atau laporkan ke Administrator bila terus terjadi.')

@section('actions')
  <a class="btn btn-ghost" href="javascript:location.reload()">Coba Lagi</a>
  <a class="btn btn-pri" href="{{ url('/dashboard') }}">Kembali ke Dasbor</a>
@endsection
