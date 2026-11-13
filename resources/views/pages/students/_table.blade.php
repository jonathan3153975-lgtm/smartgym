@if ($students->count() > 0)
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Unidades</th>
                    <th>Contato</th>
                    <th>Situação</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($students as $student)
                    @php($primary = $student->units->firstWhere('is_primary') ?? $student->units->first())
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($student->avatar_url)
                                    <img src="{{ $student->avatar_url }}" alt="{{ $student->name }}"
                                         class="h-9 w-9 flex-shrink-0 rounded-full border border-custom object-cover">
                                @else
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                         style="background: var(--color-info-soft); color: var(--color-info);">
                                        {{ strtoupper(mb_substr($student->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $student->name }}</div>
                                    <div class="text-xs text-text-secondary">
                                        {{ $student->cpf ?? 'CPF não informado' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm">
                            {{ $primary?->unit?->name ?? 'Sem unidade' }}
                            @if ($student->units->count() > 1)
                                <span class="text-xs text-text-secondary">+{{ $student->units->count() - 1 }}</span>
                            @endif
                        </td>
                        <td class="text-sm">
                            <div>{{ $student->email ?? '—' }}</div>
                            @if ($student->phone || $student->whatsapp)
                                <div class="text-xs text-text-secondary">{{ $student->whatsapp ?? $student->phone }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($student->status === 'active')
                                <span class="badge badge-success">Ativo</span>
                            @elseif ($student->status === 'pending')
                                <span class="badge badge-warning">Pendente</span>
                            @else
                                <span class="badge badge-secondary">Inativo</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('students.show', $student) }}"
                                   class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                   title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('students.edit')
                                    <button type="button"
                                            data-student-id="{{ $student->id }}"
                                            class="edit-student p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button"
                                            data-student-id="{{ $student->id }}"
                                            data-student-name="{{ $student->name }}"
                                            data-student-active="{{ $student->status === 'active' ? '1' : '0' }}"
                                            class="toggle-student p-2 rounded-lg hover:bg-surface-elevated transition-colors {{ $student->status === 'active' ? 'text-green' : 'text-text-secondary' }}"
                                            title="{{ $student->status === 'active' ? 'Inativar' : 'Ativar' }}">
                                        <i class="fas {{ $student->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                    </button>
                                @endcan
                                @can('students.delete')
                                    <button type="button"
                                            data-student-id="{{ $student->id }}"
                                            data-student-name="{{ $student->name }}"
                                            class="delete-student p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                            title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="lg:hidden space-y-3">
        @foreach ($students as $student)
            @php($primary = $student->units->firstWhere('is_primary') ?? $student->units->first())
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start gap-3">
                    @if ($student->avatar_url)
                        <img src="{{ $student->avatar_url }}" alt="{{ $student->name }}"
                             class="h-11 w-11 flex-shrink-0 rounded-full border border-custom object-cover">
                    @else
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold"
                             style="background: var(--color-info-soft); color: var(--color-info);">
                            {{ strtoupper(mb_substr($student->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $student->name }}</p>
                        <p class="text-xs text-text-secondary">{{ $student->cpf ?? 'CPF não informado' }}</p>
                    </div>
                    @if ($student->status === 'active')
                        <span class="badge badge-success whitespace-nowrap">Ativo</span>
                    @elseif ($student->status === 'pending')
                        <span class="badge badge-warning whitespace-nowrap">Pendente</span>
                    @else
                        <span class="badge badge-secondary whitespace-nowrap">Inativo</span>
                    @endif
                </div>

                <div class="mt-3 space-y-1 text-sm text-text-secondary">
                    <p><i class="fas fa-building w-4 text-center mr-1"></i>{{ $primary?->unit?->name ?? 'Sem unidade' }}</p>
                    @if ($student->email)
                        <p class="truncate"><i class="fas fa-envelope w-4 text-center mr-1"></i>{{ $student->email }}</p>
                    @endif
                </div>

                <div class="mt-3 flex items-center justify-end gap-1">
                    <a href="{{ route('students.show', $student) }}"
                       class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                       title="Visualizar">
                        <i class="fas fa-eye"></i>
                    </a>
                    @can('students.edit')
                        <button type="button"
                                data-student-id="{{ $student->id }}"
                                class="edit-student p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button"
                                data-student-id="{{ $student->id }}"
                                data-student-name="{{ $student->name }}"
                                data-student-active="{{ $student->status === 'active' ? '1' : '0' }}"
                                class="toggle-student p-2 rounded-lg hover:bg-surface transition-colors {{ $student->status === 'active' ? 'text-green' : 'text-text-secondary' }}"
                                title="{{ $student->status === 'active' ? 'Inativar' : 'Ativar' }}">
                            <i class="fas {{ $student->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                        </button>
                    @endcan
                    @can('students.delete')
                        <button type="button"
                                data-student-id="{{ $student->id }}"
                                data-student-name="{{ $student->name }}"
                                class="delete-student p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $students->links() }}
    </div>
@else
    <div class="py-12 text-center">
        <i class="fas fa-user-graduate text-4xl text-text-secondary mb-4"></i>
        <h4 class="mb-2 text-lg font-medium">
            {{ request('search') || request('status') || request('unit_id') ? 'Nenhum aluno encontrado' : 'Nenhum aluno cadastrado' }}
        </h4>
        <p class="mb-4 text-text-secondary">
            {{ request('search') || request('status') || request('unit_id') ? 'Tente alterar os filtros da busca' : 'Comece cadastrando o primeiro aluno' }}
        </p>
        @if (request('search') || request('status') || request('unit_id'))
            <a href="{{ route('students.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar filtros
            </a>
        @else
            @can('students.create')
                <button type="button" onclick="openStudentModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Cadastrar primeiro aluno
                </button>
            @endcan
        @endif
    </div>
@endif
