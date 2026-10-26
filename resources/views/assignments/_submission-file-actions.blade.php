@foreach ($submission->filePaths() as $slot => $path)
    @php($extension = strtolower(pathinfo($path, PATHINFO_EXTENSION)))
    @php($previewUrl = $extension === 'pdf'
        ? route('submissions.preview', [$submission, 'slot' => $slot])
        : (in_array($extension, ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'])
            ? 'https://view.officeapps.live.com/op/embed.aspx?src='.urlencode(\Illuminate\Support\Facades\Storage::disk('local')->temporaryUrl($path, now()->addMinutes(30)))
            : null))
    @php($downloadUrl = route('submissions.download', [$submission, 'slot' => $slot]))
    @if ($previewUrl)
        <button type="button" class="btn btn-sm {{ $buttonClass ?? '' }}" title="Lihat berkas {{ $slot }}"
                data-bs-toggle="modal" data-bs-target="#modal-preview"
                data-preview-url="{{ $previewUrl }}" data-download-url="{{ $downloadUrl }}"
                data-preview-title="{{ $title ?? 'Berkas '.$slot }} — berkas {{ $slot }}">
            <i class="ti ti-eye"></i>@if($showLabels ?? false)<span class="ms-1">Berkas {{ $slot }}</span>@endif
        </button>
    @else
        <a href="{{ $downloadUrl }}" class="btn btn-sm {{ $buttonClass ?? '' }}" title="Unduh berkas {{ $slot }}">
            <i class="ti ti-download"></i>@if($showLabels ?? false)<span class="ms-1">Berkas {{ $slot }}</span>@endif
        </a>
    @endif
@endforeach
