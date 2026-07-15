@extends('errors.layout')

@section('code', '403')
@section('title', 'Akses Ditolak')
@section('desc', 'Anda tidak memiliki hak akses untuk membuka halaman ini. Bila merasa seharusnya punya akses, hubungi Administrator Sistem.')

@section('actions')
  <a class="btn btn-pri" href="{{ url('/dashboard') }}">Kembali ke Dasbor</a>
@endsection
