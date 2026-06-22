{{-- Pesan status & error tampil sebagai toast (lihat layouts/app). Di sini hanya error validasi. --}}
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
        <div class="d-flex">
            <div><i class="ti ti-alert-triangle me-2"></i></div>
            <div>
                <h4 class="alert-title">Terdapat kesalahan pada input:</h4>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
@endif
