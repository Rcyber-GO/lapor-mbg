{{-- Field laporan bersama untuk form buat dan ubah. --}}
        <div class="form-field">
            <label for="school_id">Sekolah</label>
            <select id="school_id" name="school_id" required>
                <option value="">Pilih sekolah</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected((string) old('school_id', $report->school_id) === (string) $school->id)>{{ $school->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <label for="distribution_id">Distribusi Makanan</label>
            {{-- Opsi diisi report-form.js lewat fetch() setelah sekolah dipilih. --}}
            <select id="distribution_id" name="distribution_id" required disabled
                    data-url-template="{{ route('schools.distributions', ['school' => '__SCHOOL__']) }}"
                    data-selected="{{ old('distribution_id', $report->distribution_id) }}">
                <option value="">Pilih sekolah terlebih dahulu</option>
            </select>
        </div>
        <div class="form-field">
            <label for="reporter_relation">Hubungan Anda dengan siswa</label>
            <select id="reporter_relation" name="reporter_relation" required>
                <option value="">Pilih</option>
                @foreach ($relations as $value => $label)
                    <option value="{{ $value }}" @selected(old('reporter_relation', $report->reporter_relation) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <label for="incident_date">Tanggal keluhan muncul</label>
            <input id="incident_date" name="incident_date" type="date" value="{{ old('incident_date', $report->incident_date?->toDateString()) }}" max="{{ now()->toDateString() }}" required>
        </div>
        <div class="form-field">
            <label for="affected_count">Perkiraan jumlah siswa yang mengalami keluhan</label>
            <input id="affected_count" name="affected_count" type="number" min="1" max="5000" value="{{ old('affected_count', $report->affected_count ?? 1) }}" required>
        </div>
        <div class="form-field">
            <label for="symptoms">Keluhan yang dialami</label>
            <input id="symptoms" name="symptoms" value="{{ old('symptoms', $report->symptoms) }}" maxlength="255" placeholder="mis. mual, muntah, sakit perut" required>
        </div>
        <div class="form-field">
            <label for="description">Kronologi kejadian</label>
            <textarea id="description" name="description" rows="5" minlength="20" maxlength="3000" placeholder="Ceritakan kapan makanan dikonsumsi, kapan keluhan muncul, dan informasi lain yang Anda ketahui." required>{{ old('description', $report->description) }}</textarea>
        </div>
        <div class="form-field">
            <label for="evidence">Bukti pendukung</label>
            <input id="evidence" name="evidence" type="file" accept=".jpg,.jpeg,.png,.pdf" @required(! $report->exists)>
            <small class="muted">Foto makanan/kemasan atau surat keterangan berobat. Format JPG, PNG, atau PDF, maksimal 2 MB. Samarkan data pribadi yang tidak diperlukan.</small>
            @if ($report->exists)
                <small class="muted" style="display: block">Bukti saat ini: {{ $report->evidence_name }}. Kosongkan bila tidak ingin mengganti.</small>
            @endif
            @if ($errors->any())
                <small class="muted" style="display: block">File perlu dipilih ulang setelah ada kesalahan pada form.</small>
            @endif
        </div>
