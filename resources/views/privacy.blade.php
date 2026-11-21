@extends('public.doc')

@section('title', 'Privacy Policy')
@section('doc_title', 'Privacy policy')
@section('doc_meta', 'CA Dashboard (app.kuhu.org.in) and the CA Dashboard mobile app · Last updated 21 November 2026')

@section('doc')
    <p>CA Dashboard is practice-management software used by chartered accountancy firms and their staff. This policy explains what the website and the mobile app collect, why, and what control you have.</p>

    <h2>1. Who can use it</h2>
    <p>Only people who have been given an account by their firm can sign in. There is no public sign-up for individuals, and the service is not meant for children.</p>

    <h2>2. What we collect</h2>
    <ul>
        <li><strong>Account details</strong> – your name, email, mobile number, role and password (stored only as a secure hash).</li>
        <li><strong>Firm work data</strong> – clients, tasks, filings, invoices, payments, documents and other records that you and your colleagues enter. This data belongs to your firm.</li>
        <li><strong>Client portal uploads</strong> – documents a client uploads through a secure link sent by your firm.</li>
        <li><strong>Phone notification token</strong> – if you allow notifications in the Android app, a token from Google Firebase Cloud Messaging so we can send you alerts. It is deleted when you sign out on that phone.</li>
        <li><strong>Usage logs</strong> – an activity log of changes made inside your firm’s workspace, and standard server logs, used for security and troubleshooting.</li>
    </ul>

    <h2>3. Phone permissions</h2>
    <ul>
        <li><strong>Camera</strong> – only when you tap “Scan with camera” to photograph a document. The photo is uploaded to the client or record you chose.</li>
        <li><strong>Notifications</strong> – to show task, due-date and payment alerts. You can switch each type off in Settings → Notifications.</li>
        <li><strong>Fingerprint / face unlock</strong> – checked entirely by your phone. We never receive or store fingerprint or face data.</li>
    </ul>

    <h2>4. How the data is used</h2>
    <p>Only to run the service for your firm: showing your work, sending the reminders your firm has switched on (in-app, email, WhatsApp or phone notifications), producing invoices and reports, and keeping the system secure. We do not sell data, show ads or use the data for marketing.</p>

    <h2>5. Who it is shared with</h2>
    <p>Data is visible only to people in your own firm, according to their role and module access. It is stored on our hosting provider’s servers. Messages are delivered through the services your firm enables (email, WhatsApp Business, Google Firebase for phone notifications). We share data with authorities only when the law requires it.</p>

    <h2>6. Keeping and deleting data</h2>
    <p>Firm data is kept while the firm uses CA Dashboard, plus backups for up to one month. Your firm’s administrator can delete records, and can ask us to delete the whole workspace. To have your own account removed, see <a href="{{ route('account.delete-info') }}">Delete your account</a>.</p>

    <h2>7. Security</h2>
    <p>All traffic uses HTTPS. Each firm’s data is kept in a separate workspace, passwords are hashed, saved client portal credentials are encrypted, sessions sign out after inactivity, and the mobile app can be locked with your fingerprint or face.</p>

    <h2>8. Your rights</h2>
    <p>You can ask to see, correct or delete the personal data held about you, in line with the Digital Personal Data Protection Act, 2023. Because work records belong to your firm, most requests are handled together with your firm’s administrator.</p>

    <h2>9. Changes to this policy</h2>
    <p>If we change this policy, the new version is posted on this page with a new “last updated” date.</p>

    <h2>10. Contact</h2>
    <p>Questions or requests: <a href="mailto:rajatlakhani2@gmail.com">rajatlakhani2@gmail.com</a> or <a href="mailto:rajpopatpriyank@gmail.com">rajpopatpriyank@gmail.com</a>.</p>
@endsection
