<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2937;
        }
        .cabecalho {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }
        .cabecalho h1 {
            font-size: 16px;
            margin: 0;
            color: #111827;
        }
        .cabecalho p {
            margin: 2px 0 0;
            font-size: 9px;
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            background: #2563eb;
            color: #ffffff;
            text-align: left;
            padding: 6px 8px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-login    { background: #dbeafe; color: #1e40af; }
        .badge-logout   { background: #e5e7eb; color: #374151; }
        .badge-criacao  { background: #dcfce7; color: #166534; }
        .badge-edicao   { background: #fef9c3; color: #854d0e; }
        .badge-exclusao { background: #fee2e2; color: #991b1b; }
        .rodape {
            margin-top: 10px;
            font-size: 8px;
            color: #9ca3af;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="cabecalho">
        <div>
            <h1>Logs de Auditoria</h1>
            <p>Histórico de ações realizadas no sistema</p>
        </div>
        <p>Gerado em {{ now()->format('d/m/Y H:i:s') }} &middot; {{ $logs->count() }} registro(s)</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%">Data/Hora</th>
                <th style="width: 16%">Usuário</th>
                <th style="width: 10%">Ação</th>
                <th style="width: 45%">Descrição</th>
                <th style="width: 12%">IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                @php
                    $classeBadge = match ($log->action) {
                        'Login'    => 'badge-login',
                        'Logout'   => 'badge-logout',
                        'Criação'  => 'badge-criacao',
                        'Edição'   => 'badge-edicao',
                        'Exclusão' => 'badge-exclusao',
                        default    => 'badge-logout',
                    };
                @endphp
                <tr>
                    <td>
                        {{ $log->created_at->format('d/m/Y') }}<br>
                        <span style="color:#9ca3af">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td>
                        {{ $log->user?->name ?? 'Sistema' }}<br>
                        <span style="color:#9ca3af">{{ $log->user?->email ?? '-' }}</span>
                    </td>
                    <td><span class="badge {{ $classeBadge }}">{{ $log->action }}</span></td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->ip_address ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="rodape">SIGE &mdash; Sistema de Gestão de Estoque</div>
</body>
</html>