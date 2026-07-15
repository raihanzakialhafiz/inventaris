@extends('errors.layout')

@section('code', '503')
@section('title', 'Sedang Pemeliharaan')
@section('desc', 'Sistem sedang dalam pemeliharaan terjadwal dan akan kembali normal sebentar lagi. Terima kasih atas kesabarannya.')

@section('actions')
  <a class="btn btn-pri" href="javascript:location.reload()">Muat Ulang</a>
@endsection
