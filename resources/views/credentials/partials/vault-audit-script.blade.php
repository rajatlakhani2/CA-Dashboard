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
            alert(action === 'copied_username' ? 'Nothing to copy.' : 'Password is empty or cannot be decrypted. Re-save the entry in the client profile.');
            return;
        }

        const done = () => {
            credentialVaultAudit(auditUrl, action);
            alert(action === 'copied_username' ? 'Copied User ID' : 'Copied Password');
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(done).catch(() => {
                window.prompt('Copy manually:', value);
                done();
            });
        } else {
            window.prompt('Copy manually:', value);
            done();
        }
    }
</script>
@endonce
