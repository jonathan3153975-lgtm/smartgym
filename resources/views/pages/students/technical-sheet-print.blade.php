<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório - {{ $student->name }}</title>
    @php
        $assessment = $bodySummary['current'];
        $previousAssessment = $bodySummary['previous'];
    @endphp
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1f2937; margin: 0; background: #f3f4f6; }
        .page { max-width: 960px; margin: 0 auto; padding: 32px; background: #fff; }
        h1 { margin: 0; font-size: 24px; }
        h2 { margin: 28px 0 10px; font-size: 16px; border-bottom: 1px solid #d1d5db; padding-bottom: 6px; }
        h3 { margin: 0 0 6px; font-size: 13px; color: #4b5563; }
        .muted { color: #6b7280; font-size: 12px; }
        .header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #111827; padding-bottom: 16px; }
        .header-meta { text-align: right; font-size: 12px; color: #6b7280; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-top: 14px; }
        .card { border: 1px solid #d1d5db; border-radius: 8px; padding: 10px; }
        .card .label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
        .card .value { margin-top: 4px; font-size: 18px; font-weight: 700; }
        .card .meta { margin-top: 3px; font-size: 11px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        th { background: #f9fafb; color: #4b5563; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .text-right { text-align: right; }
        .badge { display: inline-block; border-radius: 999px; padding: 2px 8px; font-size: 10px; font-weight: 700; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #e5e7eb; color: #374151; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        ul { margin: 6px 0 0; padding-left: 18px; }
        li { margin-bottom: 3px; font-size: 12px; }
        .print-button { position: fixed; right: 24px; top: 24px; border: 0; border-radius: 8px; background: #111827; color: #fff; padding: 10px 16px; font-weight: 700; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .page { max-width: none; padding: 0; }
            .print-button { display: none; }
            h2 { page-break-after: avoid; }
            section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <button type="button" class="print-button" onclick="window.print()">Imprimir relatório</button>

    <div class="page">
        <div class="header">
            <div>
                <h1>Relatório de Avaliação e Atividades Físicas</h1>
                <p class="muted" style="margin: 6px 0 0;">{{ $student->network?->name ?? 'Rede' }}</p>
            </div>
            <div class="header-meta">
                <p style="margin: 0; font-weight: 700; color: #111827;">{{ $student->name }}</p>
                <p style="margin: 3px 0 0;">{{ $student->cpf ?? 'CPF não informado' }}</p>
                <p style="margin: 3px 0 0;">Emitido em {{ now()->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <h2>Resumo da avaliação</h2>
        <div class="grid">
            <div class="card">
                <p class="label">Peso atual</p>
                <p class="value">{{ $bodySummary['cards']['weight']['value'] }}</p>
                <p class="meta">{{ $bodySummary['cards']['weight']['comparison'] ?? 'Sem comparação anterior' }}</p>
            </div>
            <div class="card">
                <p class="label">Variação total</p>
                <p class="value">{{ $bodySummary['cards']['total']['value'] }}</p>
                <p class="meta">{{ $bodySummary['cards']['total']['meta'] }}</p>
            </div>
            <div class="card">
                <p class="label">IMC</p>
                <p class="value">{{ $bodySummary['cards']['bmi']['value'] }}</p>
                <p class="meta">{{ $bodySummary['cards']['bmi']['meta'] }}</p>
            </div>
            <div class="card">
                <p class="label">Gordura / cintura</p>
                <p class="value">{{ $bodySummary['cards']['fat_waist']['value'] }}</p>
                <p class="meta">{{ $bodySummary['cards']['fat_waist']['meta'] }}</p>
            </div>
        </div>

        @if ($assessment)
            <h2>Avaliação atual</h2>
            <div class="two-col">
                <section>
                    <h3>Objetivo e rotina</h3>
                    <table>
                        <tr><th>Objetivo</th><td>{{ $assessment->goal_label }}</td></tr>
                        <tr><th>Experiência</th><td>{{ $assessment->experience_level_label }}</td></tr>
                        <tr><th>Frequência semanal</th><td>{{ $assessment->training_frequency }}x</td></tr>
                        <tr><th>Enviada em</th><td>{{ $assessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
                        <tr><th>PAR-Q</th><td>{{ $assessment->parq_positive ? 'Com alerta' : 'Sem alerta' }}</td></tr>
                    </table>
                    @if ($assessment->goal_notes)
                        <p class="muted" style="margin-top: 8px;">{{ $assessment->goal_notes }}</p>
                    @endif
                </section>

                <section>
                    <h3>Histórico de saúde</h3>
                    <table>
                        <tr><th>Condições</th><td>{{ $assessment->medical_conditions ?: 'Nenhuma' }}</td></tr>
                        <tr><th>Medicamentos</th><td>{{ $assessment->medications ?: 'Nenhum' }}</td></tr>
                        <tr><th>Lesões</th><td>{{ $assessment->injuries ?: 'Nenhuma' }}</td></tr>
                        <tr><th>Cirurgias</th><td>{{ $assessment->surgeries ?: 'Nenhuma' }}</td></tr>
                        <tr><th>Fumante</th><td>{{ $assessment->smoker ? 'Sim' : 'Não' }}</td></tr>
                        <tr><th>Álcool</th><td>{{ $assessment->alcohol_consumption_label }}</td></tr>
                    </table>
                </section>
            </div>

            <h2>Medidas corporais</h2>
            <table>
                <thead>
                    <tr>
                        <th>Medida</th>
                        <th class="text-right">Avaliação atual</th>
                        <th class="text-right">Avaliação anterior</th>
                        <th class="text-right">Variação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bodySummary['metrics'] as $field => $metric)
                        <tr>
                            <td>{{ $metric['label'] }}</td>
                            <td class="text-right">{{ $metric['value'] }}</td>
                            <td class="text-right">
                                {{ $bodySummary['previous_metrics'][$field]['value'] ?? '—' }}
                            </td>
                            <td class="text-right">{{ $metric['percent_label'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h2>Questionário de prontidão (PAR-Q)</h2>
            <ul>
                @foreach (\App\Models\StudentAssessment::PARQ_QUESTIONS as $key => $question)
                    @php($positive = filter_var($assessment->parq_answers[$key] ?? false, FILTER_VALIDATE_BOOL))
                    <li>{{ $question }} — <strong>{{ $positive ? 'Sim' : 'Não' }}</strong></li>
                @endforeach
            </ul>

            @if ($assessment->observations || $assessment->trainer_notes)
                <h2>Observações</h2>
                @if ($assessment->observations)
                    <p style="font-size: 12px; white-space: pre-line;">{{ $assessment->observations }}</p>
                @endif
                @if ($assessment->trainer_notes)
                    <p class="muted" style="margin-top: 8px;">Observação do treinador</p>
                    <p style="font-size: 12px; white-space: pre-line;">{{ $assessment->trainer_notes }}</p>
                @endif
            @endif
        @else
            <h2>Avaliação atual</h2>
            <p class="muted">Nenhuma avaliação registrada.</p>
        @endif

        <h2>Atividades físicas entre avaliações</h2>
        <p class="muted">
            Período: {{ $activity['from']->format('d/m/Y') }} a {{ $activity['to']->format('d/m/Y') }}.
            @if (! $previousAssessment)
                Não há avaliação anterior; consideramos os últimos 30 dias.
            @endif
        </p>

        <div class="grid">
            <div class="card">
                <p class="label">Treinos concluídos</p>
                <p class="value">{{ $activity['workouts'] }}</p>
            </div>
            <div class="card">
                <p class="label">Aulas realizadas</p>
                <p class="value">{{ $activity['classes'] }}</p>
            </div>
            <div class="card">
                <p class="label">Check-ins</p>
                <p class="value">{{ $activity['checkins'] }}</p>
            </div>
            <div class="card">
                <p class="label">Dias ativos</p>
                <p class="value">{{ $activity['activity_days'] }}</p>
            </div>
        </div>

        <h3 style="margin-top: 18px;">Extrato de treinos</h3>
        @if ($activity['workout_logs'] === [])
            <p class="muted">Nenhum treino concluído no período.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Treino</th>
                        <th>Duração</th>
                        <th class="text-right">Séries</th>
                        <th class="text-right">Volume</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activity['workout_logs'] as $log)
                        <tr>
                            <td>{{ $log['date'] }}</td>
                            <td>{{ $log['name'] }}</td>
                            <td>{{ $log['duration'] }}</td>
                            <td class="text-right">{{ $log['sets'] }}</td>
                            <td class="text-right">{{ $log['volume'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3 style="margin-top: 18px;">Grupos musculares treinados</h3>
        @if ($activity['muscle_groups'] === [])
            <p class="muted">Nenhuma série registrada no período.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Grupo muscular</th>
                        <th class="text-right">Repetições</th>
                        <th class="text-right">Participação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activity['muscle_groups'] as $group)
                        <tr>
                            <td>{{ $group['label'] }}</td>
                            <td class="text-right">{{ $group['reps'] }}</td>
                            <td class="text-right">{{ $group['percent'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h2>Histórico de avaliações</h2>
        @if ($assessmentHistory->isEmpty())
            <p class="muted">Nenhuma avaliação registrada.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Objetivo</th>
                        <th>Experiência</th>
                        <th class="text-right">Peso</th>
                        <th class="text-right">IMC</th>
                        <th>PAR-Q</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assessmentHistory as $historyAssessment)
                        <tr>
                            <td>{{ $historyAssessment->submitted_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $historyAssessment->goal_label }}</td>
                            <td>{{ $historyAssessment->experience_level_label }}</td>
                            <td class="text-right">{{ $historyAssessment->weight_kg ? number_format((float) $historyAssessment->weight_kg, 2, ',', '.').' kg' : '—' }}</td>
                            <td class="text-right">{{ $historyAssessment->bmi ? number_format($historyAssessment->bmi, 1, ',', '.') : '—' }}</td>
                            <td>{{ $historyAssessment->parq_positive ? 'Alerta' : 'Sem alerta' }}</td>
                            <td>{{ $historyAssessment->reviewed_at ? 'Verificada' : 'A verificar' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p class="muted" style="margin-top: 28px; border-top: 1px solid #e5e7eb; padding-top: 10px;">
            Relatório gerado pelo SmartGym em {{ now()->format('d/m/Y H:i') }}.
        </p>
    </div>
</body>
</html>
