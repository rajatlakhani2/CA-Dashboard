@once
<script>
    function credentialVaultAudit(url, action) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!url || !token) {
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ action }),
        }).catch(() => {});
    }

    function credentialVaultTogglePassword(button, auditUrl) {
        const input = document.getElementById(button.dataset.target);
        if (!input) {
            return;
        }

        const revealing = input.type === 'password';
        input.type = revealing ? 'text' : 'password';

        if (revealing) {
            credentialVaultAudit(auditUrl, 'revealed_password');
        }
    }

    function credentialVaultCopy(button, auditUrl, action) {
        const value = button.dataset.copyValue || '';
        if (!value) {
            return;
        }

        navigator.clipboard.writeText(value).then(() => {
            credentialVaultAudit(auditUrl, action);
            alert(action === 'copied_username' ? 'Copied User ID' : 'Copied Password');
        });
    }
</script>
@endonce
