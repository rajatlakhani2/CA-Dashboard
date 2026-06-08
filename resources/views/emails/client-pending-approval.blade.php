<p>Hello,</p>

<p>A new client was submitted by <strong>{{ $client->createdBy?->name ?? 'a team member' }}</strong> and needs your approval before it appears firm-wide.</p>

<ul>
    <li><strong>Name:</strong> {{ $client->name }}</li>
    <li><strong>PAN:</strong> {{ $client->pan }}</li>
    @if($client->group_name)
    <li><strong>Group:</strong> {{ $client->group_name }}</li>
    @endif
</ul>

<p>
    <a href="{{ route('clients.index') }}">Open Clients to approve</a>
</p>

<p>— CA Dashboard</p>
