@extends('errors.layout')
@section('code', '403')
@section('title', 'Akses ditolak')
@section('desc', ($exception && $exception->getMessage()) ? $exception->getMessage() : 'Anda tidak memiliki izin untuk membuka halaman ini.')
