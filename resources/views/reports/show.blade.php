@extends('layouts.app')

@section('title', 'Detail Laporan #'.$report->id)

@section('content')
<section class="panel" style="max-width: 820px">
    <div class="page-title-row">
        <h1 class="page-heading">Laporan #{{ $report->id }}</h1>
        <span class="status-badge status-{{ $report->status }}">{{ $report->statusLabel() }}</span>
    </div>
    <p class="muted">Dikirim {{ $report->created_at->translatedFormat('d M Y, H:i') }}</p>

    @can('update', $report)
        @if ($report->isEditable())
            <div class="alert {{ $report->status === App\Models\Report::STATUS_PERLU_DILENGKAPI ? 'alert-error' : 'alert-success' }}" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap">
                <span>
                    @if ($report->status === App\Models\Report::STATUS_PERLU_DILENGKAPI)
                        Pemantau meminta laporan ini dilengkapi. Lihat catatan di bawah, lalu perbaiki laporan.
                    @else
                        Laporan masih dapat diubah sampai mulai ditinjau Pemantau.
                    @endif
                </span>
                <a class="button button-small" href="{{ route('reports.edit', $report) }}">Ubah Laporan</a>
            </div>
        @endif
    @endcan

    <dl class="detail-list">
        @can('viewAny', App\Models\Report::class)
            {{-- Hanya nama yang ditampilkan; kontak pribadi pelapor tidak diperlukan untuk peninjauan. --}}
            <dt>Pelapor</dt>
            <dd>{{ $report->user->name }}</dd>
        @endcan

        <dt>Sekolah</dt>
        <dd>{{ $report->school->name }}</dd>

        <dt>Distribusi</dt>
        <dd>{{ $report->distribution->distribution_date->translatedFormat('d M Y') }} &middot; {{ $report->distribution->menu }} ({{ $report->distribution->provider_name }})</dd>

        <dt>Hubungan dengan siswa</dt>
        <dd>{{ $report->relationLabel() }}</dd>

        <dt>Tanggal keluhan muncul</dt>
        <dd>{{ $report->incident_date->translatedFormat('d M Y') }}</dd>

        <dt>Perkiraan siswa terdampak</dt>
        <dd>{{ $report->affected_count }} orang</dd>

        <dt>Keluhan</dt>
        <dd>{{ $report->symptoms }}</dd>

        <dt>Kronologi</dt>
        <dd style="white-space: pre-line">{{ $report->description }}</dd>

        <dt>Bukti pendukung</dt>
        <dd>
            <a href="{{ route('reports.evidence', $report) }}" target="_blank" rel="noopener">{{ $report->evidence_name }}</a>
            @if ($report->evidenceIsImage())
                <a href="{{ route('reports.evidence', $report) }}" target="_blank" rel="noopener" style="display: block; margin-top: 10px">
                    <img src="{{ route('reports.evidence', $report) }}" alt="Bukti pendukung laporan #{{ $report->id }}" class="evidence-preview">
                </a>
            @endif
        </dd>
    </dl>

    @if ($related)
        <h2 class="section-heading">Keterkaitan Laporan</h2>
        <p style="margin-top: 0">
            <strong>{{ $related['sameDistribution']->count() }}</strong> laporan lain pada distribusi yang sama
            @if ($related['sameDistribution']->isNotEmpty())
                (total perkiraan {{ $related['sameDistribution']->sum('affected_count') + $report->affected_count }} siswa terdampak, termasuk laporan ini)
            @endif
            &middot; <strong>{{ $related['sameSchoolCount'] }}</strong> laporan lain dari {{ $report->school->name }} (semua distribusi).
        </p>
        @if ($related['sameDistribution']->isNotEmpty())
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>No.</th><th>Dikirim</th><th>Terdampak</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($related['sameDistribution'] as $other)
                            <tr>
                                <td>#{{ $other->id }}</td>
                                <td>{{ $other->created_at->translatedFormat('d M Y') }}</td>
                                <td>{{ $other->affected_count }} orang</td>
                                <td><span class="status-badge status-{{ $other->status }}">{{ $other->statusLabel() }}</span></td>
                                <td><a class="button button-secondary button-small" href="{{ route('reports.show', $other) }}">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    <h2 class="section-heading">Catatan Peninjauan</h2>
    @if ($report->notes->isEmpty())
        <p class="muted">Belum ada catatan.</p>
    @else
        <ul class="note-list">
            @foreach ($report->notes as $note)
                <li class="note-item">
                    <div class="note-meta">
                        {{-- Pelapor hanya melihat peran penulis, bukan nama Pemantau. --}}
                        @can('viewAny', App\Models\Report::class)
                            {{ $note->user->name }}
                        @else
                            Pemantau Komunitas
                        @endcan
                        &middot; {{ $note->created_at->translatedFormat('d M Y, H:i') }}
                        @if ($note->statusLabel())
                            &middot; status menjadi <strong>{{ $note->statusLabel() }}</strong>
                        @endif
                    </div>
                    <div style="white-space: pre-line">{{ $note->note }}</div>
                </li>
            @endforeach
        </ul>
    @endif

    @can('review', $report)
        @unless ($report->isFinal())
            <div class="action-panel">
                <h3>Tindakan Peninjauan</h3>
                @if ($report->reviewTransitions())
                    <form method="POST" action="{{ route('reports.status', $report) }}">
                        @csrf
                        <div class="form-field">
                            <label for="review_note">Catatan</label>
                            <textarea id="review_note" name="note" rows="3" maxlength="2000" placeholder="Wajib diisi untuk tindakan bertanda *">{{ old('to_status') ? old('note') : '' }}</textarea>
                        </div>
                        <div class="action-buttons">
                            @foreach ($report->reviewTransitions() as $toStatus => $noteRequired)
                                <button class="button button-small" type="submit" name="to_status" value="{{ $toStatus }}">
                                    {{ App\Models\Report::REVIEW_ACTION_LABELS[$toStatus] }}{{ $noteRequired ? ' *' : '' }}
                                </button>
                            @endforeach
                        </div>
                    </form>
                @else
                    <p class="muted" style="margin-top: 0">Tidak ada perubahan status yang dapat dilakukan Pemantau pada status ini.</p>
                @endif

                <form method="POST" action="{{ route('reports.notes.store', $report) }}" style="margin-top: 16px">
                    @csrf
                    <div class="form-field">
                        <label for="extra_note">Tambah catatan tanpa mengubah status</label>
                        <textarea id="extra_note" name="note" rows="2" maxlength="2000" required></textarea>
                    </div>
                    <button class="button button-secondary button-small" type="submit">Simpan Catatan</button>
                </form>
            </div>
        @endunless
    @endcan

    <h2 class="section-heading">Penerusan Informasi</h2>
    @if ($report->forwardings->isEmpty())
        <p class="muted">Belum ada penerusan informasi kepada pihak terkait.</p>
    @else
        <ul class="note-list">
            @foreach ($report->forwardings as $forwarding)
                <li class="note-item">
                    <div class="note-meta">
                        Diteruskan {{ $forwarding->forwarded_at->translatedFormat('d M Y') }}
                        @can('viewAny', App\Models\Report::class)
                            &middot; dicatat oleh {{ $forwarding->user->name }}
                        @endcan
                    </div>
                    <strong>{{ $forwarding->recipientTypeLabel() }}</strong>: {{ $forwarding->recipient_name }}
                    {{-- Catatan penerusan bersifat internal untuk staf. --}}
                    @can('viewAny', App\Models\Report::class)
                        @if ($forwarding->note)
                            <div style="white-space: pre-line; margin-top: 4px">{{ $forwarding->note }}</div>
                        @endif
                    @endcan
                </li>
            @endforeach
        </ul>
    @endif

    @can('forward', $report)
        @if ($report->isForwardable())
            <div class="action-panel">
                <h3>Catat Penerusan</h3>
                <form method="POST" action="{{ route('reports.forwardings.store', $report) }}">
                    @csrf
                    <div class="form-field">
                        <label for="recipient_type">Jenis pihak</label>
                        <select id="recipient_type" name="recipient_type" required>
                            <option value="">Pilih</option>
                            @foreach (App\Models\Forwarding::RECIPIENT_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('recipient_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="recipient_name">Nama pihak</label>
                        <input id="recipient_name" name="recipient_name" value="{{ old('recipient_name') }}" maxlength="150" placeholder="mis. Kepala Sekolah {{ $report->school->name }}" required>
                    </div>
                    <div class="form-field">
                        <label for="forwarded_at">Tanggal diteruskan</label>
                        <input id="forwarded_at" name="forwarded_at" type="date" value="{{ old('forwarded_at', now()->toDateString()) }}" min="{{ $report->created_at->toDateString() }}" max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="form-field">
                        <label for="forwarding_note">Catatan (opsional)</label>
                        <textarea id="forwarding_note" name="note" rows="2" maxlength="2000">{{ old('recipient_type') ? old('note') : '' }}</textarea>
                    </div>
                    <button class="button button-small" type="submit">Simpan Penerusan</button>
                </form>
            </div>
        @elseif (! $report->isFinal())
            <p class="muted">Penerusan dapat dicatat setelah Pemantau menandai laporan ini untuk diteruskan.</p>
        @endif
    @endcan

    <div class="form-actions" style="margin-top: 24px">
        @can('viewAny', App\Models\Report::class)
            <a class="button button-secondary" href="{{ route('reports.incoming') }}">&larr; Kembali ke Laporan Masuk</a>
        @elsecan('create-report')
            <a class="button button-secondary" href="{{ route('reports.index') }}">&larr; Kembali ke Laporan Saya</a>
        @endcan
    </div>
</section>
@endsection
