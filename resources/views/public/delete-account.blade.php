@extends('public.doc')

@section('title', 'Delete your account')
@section('doc_title', 'Delete your account')
@section('doc_meta', 'How to remove your CA Dashboard account and what happens to your data')

@section('doc')
    <p>CA Dashboard accounts are created by a firm for its own people, so an account is removed by the firm or, if needed, by us on request. This applies to both the website and the mobile app.</p>

    <h2>Option 1 – Ask your firm (fastest)</h2>
    <p>Ask your firm’s partner or administrator to remove your account. Once removed, you can no longer sign in on any device.</p>

    <h2>Option 2 – Email us</h2>
    <p>Send an email from the address you sign in with to <a href="mailto:rajatlakhani2@gmail.com?subject=Delete%20my%20CA%20Dashboard%20account">rajatlakhani2@gmail.com</a> or <a href="mailto:rajpopatpriyank@gmail.com?subject=Delete%20my%20CA%20Dashboard%20account">rajpopatpriyank@gmail.com</a> with:</p>
    <ul>
        <li>the subject “Delete my CA Dashboard account”;</li>
        <li>your name and your firm’s workspace ID.</li>
    </ul>
    <p>We confirm the request with your firm and complete it within 30 days.</p>

    <h2>What is deleted</h2>
    <ul>
        <li>Your sign-in, password and profile details (name, email, mobile number).</li>
        <li>Phone notification tokens and saved app settings for your account.</li>
    </ul>

    <h2>What the firm keeps</h2>
    <p>Work you did for the firm (tasks, notes, time logs, invoices, client records and the activity log) belongs to the firm and stays in its workspace, because the firm may need it for professional and legal record-keeping. Backups containing your details are removed automatically within one month.</p>

    <h2>Deleting a whole firm workspace</h2>
    <p>A firm’s partner can ask us by email to delete the entire workspace and all its data. We can provide an export first if requested.</p>
@endsection
