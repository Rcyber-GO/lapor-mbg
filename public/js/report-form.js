// Form laporan: memuat distribusi sesuai sekolah yang dipilih tanpa reload halaman.
// Browser hanya membantu memilih; validasi kecocokan sekolah-distribusi tetap di server.
document.addEventListener('DOMContentLoaded', () => {
    const schoolSelect = document.getElementById('school_id');
    const distributionSelect = document.getElementById('distribution_id');

    if (!schoolSelect || !distributionSelect) {
        return;
    }

    const urlTemplate = distributionSelect.dataset.urlTemplate;
    let controller = null;

    function showPlaceholder(text) {
        distributionSelect.innerHTML = '';
        distributionSelect.add(new Option(text, ''));
        distributionSelect.disabled = true;
    }

    async function loadDistributions(schoolId, selectedId = '') {
        // Batalkan request sebelumnya agar respons lama tidak menimpa pilihan sekolah terbaru.
        controller?.abort();

        if (!schoolId) {
            showPlaceholder('Pilih sekolah terlebih dahulu');
            return;
        }

        controller = new AbortController();
        showPlaceholder('Memuat distribusi…');

        try {
            const response = await fetch(urlTemplate.replace('__SCHOOL__', encodeURIComponent(schoolId)), {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const distributions = await response.json();

            if (distributions.length === 0) {
                showPlaceholder('Belum ada distribusi untuk sekolah ini');
                return;
            }

            showPlaceholder('Pilih distribusi');
            distributions.forEach((distribution) => {
                const isSelected = String(distribution.id) === String(selectedId);
                distributionSelect.add(new Option(distribution.label, distribution.id, isSelected, isSelected));
            });
            distributionSelect.disabled = false;
        } catch (error) {
            if (error.name !== 'AbortError') {
                showPlaceholder('Gagal memuat distribusi, silakan pilih ulang sekolah');
            }
        }
    }

    schoolSelect.addEventListener('change', () => loadDistributions(schoolSelect.value));

    // Setelah validasi gagal, sekolah lama masih terpilih: muat ulang dan pilih kembali distribusinya.
    loadDistributions(schoolSelect.value, distributionSelect.dataset.selected);
});
