@php
    $studentTenant = \App\Support\TenantContext::current();
    $studentUnitOptions = $studentTenant->unitsQuery()->get(['id', 'name']);
    $studentShareAcrossUnits = \App\Support\NetworkPolicy::get(
        $studentTenant->network(),
        'pessoas',
        'share_across_units',
        true
    );
@endphp

{{-- Student create/edit modal --}}
<div id="studentModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="studentModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-6 flex items-center justify-between">
            <h2 id="studentModalTitle" class="text-xl font-bold">Novo aluno</h2>
            <button type="button" onclick="closeStudentModal()"
                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="studentForm" method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="studentFormMethod" value="POST">

            {{-- Personal data --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Dados pessoais</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="student_name" class="label-field">Nome completo <span class="text-red">*</span></label>
                        <input type="text" id="student_name" name="name" required maxlength="255" class="input-field"
                               placeholder="Nome completo do aluno">
                    </div>

                    <div>
                        <label for="student_social_name" class="label-field">Nome social</label>
                        <input type="text" id="student_social_name" name="social_name" maxlength="255" class="input-field">
                    </div>

                    <div>
                        <label for="student_cpf" class="label-field">CPF</label>
                        <input type="text" id="student_cpf" name="cpf" maxlength="14" class="input-field"
                               placeholder="000.000.000-00" inputmode="numeric" autocomplete="off">
                    </div>

                    <div>
                        <label for="student_birth_date" class="label-field">Data de nascimento</label>
                        <input type="date" id="student_birth_date" name="birth_date" class="input-field">
                    </div>

                    <div>
                        <label for="student_gender" class="label-field">Sexo / gênero</label>
                        <select id="student_gender" name="gender" class="input-field">
                            <option value="">Não informado</option>
                            <option value="male">Masculino</option>
                            <option value="female">Feminino</option>
                            <option value="other">Outro</option>
                            <option value="prefer_not_to_say">Prefiro não informar</option>
                        </select>
                    </div>

                    <div>
                        <label for="student_email" class="label-field">E-mail</label>
                        <input type="email" id="student_email" name="email" maxlength="255" class="input-field">
                    </div>

                    <div>
                        <label for="student_phone" class="label-field">Telefone</label>
                        <input type="text" id="student_phone" name="phone" maxlength="20" class="input-field"
                               placeholder="(00) 0000-0000" data-phone>
                    </div>

                    <div>
                        <label for="student_whatsapp" class="label-field">WhatsApp</label>
                        <input type="text" id="student_whatsapp" name="whatsapp" maxlength="20" class="input-field"
                               placeholder="(00) 00000-0000" data-phone>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="student_avatar" class="label-field">Foto do aluno</label>
                        <input type="file" id="student_avatar" name="avatar"
                               accept="image/png,image/jpeg,image/webp"
                               class="input-field file:mr-3 file:rounded-md file:border-0 file:bg-cyan/10 file:px-3 file:py-1 file:text-sm file:font-medium file:text-cyan">
                        <p class="mt-1 text-xs text-text-secondary">Opcional. PNG, JPG ou WebP, até 2 MB.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <div id="studentAvatarPreviewWrap" class="hidden items-center gap-3 rounded-xl border border-custom bg-bg-secondary p-3">
                            <img id="studentAvatarPreview" src="" alt="Prévia da foto" class="h-20 w-20 rounded-full object-cover">
                            <div>
                                <p class="text-sm font-medium">Foto atual</p>
                                <label class="mt-1 inline-flex cursor-pointer items-center gap-2 text-sm text-red">
                                    <input type="checkbox" name="remove_avatar" value="1">
                                    Remover foto
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Address --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Endereço</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-6" data-cep-scope>
                    <div class="sm:col-span-2">
                        <label for="student_zip_code" class="label-field">CEP</label>
                        <input type="text" id="student_zip_code" name="zip_code" maxlength="9" class="input-field"
                               placeholder="00000-000" data-cep>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="student_street" class="label-field">Rua / Logradouro</label>
                        <input type="text" id="student_street" name="street" maxlength="255" class="input-field"
                               data-cep-field="street">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="student_number" class="label-field">Número</label>
                        <input type="text" id="student_number" name="number" maxlength="20" class="input-field"
                               data-cep-field="number">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="student_complement" class="label-field">Complemento</label>
                        <input type="text" id="student_complement" name="complement" maxlength="255" class="input-field">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="student_neighborhood" class="label-field">Bairro</label>
                        <input type="text" id="student_neighborhood" name="neighborhood" maxlength="255" class="input-field"
                               data-cep-field="neighborhood">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="student_city" class="label-field">Cidade</label>
                        <input type="text" id="student_city" name="city" maxlength="255" class="input-field"
                               data-cep-field="city">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="student_state" class="label-field">UF</label>
                        <input type="text" id="student_state" name="state" maxlength="2" class="input-field uppercase"
                               data-cep-field="state">
                    </div>
                </div>
            </section>

            {{-- Emergency contact --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Contato de emergência</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="student_emergency_contact_name" class="label-field">Nome</label>
                        <input type="text" id="student_emergency_contact_name" name="emergency_contact_name" maxlength="255" class="input-field">
                    </div>

                    <div>
                        <label for="student_emergency_contact_phone" class="label-field">Telefone</label>
                        <input type="text" id="student_emergency_contact_phone" name="emergency_contact_phone" maxlength="20" class="input-field"
                               placeholder="(00) 00000-0000" data-phone>
                    </div>

                    <div>
                        <label for="student_emergency_contact_relationship" class="label-field">Parentesco</label>
                        <input type="text" id="student_emergency_contact_relationship" name="emergency_contact_relationship" maxlength="100" class="input-field"
                               placeholder="Mãe, pai, cônjuge...">
                    </div>
                </div>
            </section>

            {{-- Student access --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Acesso do aluno</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="student_access_password" class="label-field">Senha de acesso</label>
                        <input type="password" id="student_access_password" name="access_password" class="input-field"
                               autocomplete="new-password" placeholder="Mínimo de 8 caracteres">
                        <p class="mt-1 text-xs text-text-secondary">
                            A senha é temporária e o aluno deverá alterá-la no primeiro acesso.
                        </p>
                    </div>

                    <div>
                        <label for="student_access_password_confirmation" class="label-field">Confirmar senha</label>
                        <input type="password" id="student_access_password_confirmation" name="access_password_confirmation"
                               class="input-field" autocomplete="new-password">
                    </div>

                    <p id="studentAccessHint" class="hidden text-xs text-cyan sm:col-span-2"></p>

                    <p class="text-xs text-text-secondary sm:col-span-2">
                        O e-mail de acesso é o e-mail informado no cadastro do aluno.
                        No futuro, o envio da senha temporária será feito por e-mail.
                    </p>
                </div>
            </section>

            {{-- Organization --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Organização</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="student_primary_unit_id" class="label-field">Unidade principal <span class="text-red">*</span></label>
                        <select id="student_primary_unit_id" name="primary_unit_id" required class="input-field">
                            <option value="">Selecione a unidade</option>
                            @foreach ($studentUnitOptions as $unitOption)
                                <option value="{{ $unitOption->id }}" @selected($studentUnitOptions->count() === 1)>{{ $unitOption->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="student_status" class="label-field">Situação <span class="text-red">*</span></label>
                        <select id="student_status" name="status" required class="input-field">
                            <option value="active">Ativo</option>
                            <option value="pending">Pendente</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>

                    <div>
                        <label for="student_source" class="label-field">Origem do cadastro</label>
                        <select id="student_source" name="source" class="input-field">
                            <option value="">Não informada</option>
                            <option value="walk_in">Presencial</option>
                            <option value="online">Online</option>
                            <option value="referral">Indicação</option>
                            <option value="campaign">Campanha</option>
                            <option value="partnership">Parceria</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>

                    @if ($studentShareAcrossUnits)
                        <div class="sm:col-span-2">
                            <p class="label-field">Unidades adicionais</p>
                            <div class="grid max-h-44 grid-cols-1 gap-2 overflow-y-auto rounded-xl border border-custom bg-bg-secondary p-3 sm:grid-cols-2">
                                @forelse ($studentUnitOptions as $unitOption)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-custom bg-surface px-3 py-2 text-sm transition-colors hover:border-cyan">
                                        <input type="checkbox" name="additional_unit_ids[]" value="{{ $unitOption->id }}"
                                               class="rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan">
                                        <span>{{ $unitOption->name }}</span>
                                    </label>
                                @empty
                                    <p class="text-sm text-text-secondary">Nenhuma unidade disponível.</p>
                                @endforelse
                            </div>
                            <p class="mt-1 text-xs text-text-secondary">
                                A unidade principal já é vinculada automaticamente.
                            </p>
                        </div>
                    @else
                        <p class="sm:col-span-2 text-xs text-text-secondary">
                            As configurações da rede permitem apenas uma unidade por aluno.
                        </p>
                    @endif

                    <div class="sm:col-span-2">
                        <label for="student_admin_notes" class="label-field">Observações administrativas</label>
                        <textarea id="student_admin_notes" name="admin_notes" rows="3" maxlength="2000" class="input-field"></textarea>
                    </div>
                </div>
            </section>

            <p id="studentFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3 border-t border-custom pt-4">
                <button type="button" onclick="closeStudentModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary" id="studentSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="studentSubmitBtnText">Salvar aluno</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('studentModal');
        const form = document.getElementById('studentForm');
        const title = document.getElementById('studentModalTitle');
        const methodInput = document.getElementById('studentFormMethod');
        const submitText = document.getElementById('studentSubmitBtnText');
        const submitBtn = document.getElementById('studentSubmitBtn');
        const errorBox = document.getElementById('studentFormError');
        const cpfInput = document.getElementById('student_cpf');
        const avatarInput = document.getElementById('student_avatar');
        const avatarPreviewWrap = document.getElementById('studentAvatarPreviewWrap');
        const avatarPreview = document.getElementById('studentAvatarPreview');
        const removeAvatar = form.querySelector('input[name="remove_avatar"]');
        const primaryUnitSelect = document.getElementById('student_primary_unit_id');
        const accessHint = document.getElementById('studentAccessHint');

        if (!modal || !form) {
            return;
        }

        const fields = {
            name: document.getElementById('student_name'),
            social_name: document.getElementById('student_social_name'),
            cpf: cpfInput,
            birth_date: document.getElementById('student_birth_date'),
            gender: document.getElementById('student_gender'),
            email: document.getElementById('student_email'),
            phone: document.getElementById('student_phone'),
            whatsapp: document.getElementById('student_whatsapp'),
            zip_code: document.getElementById('student_zip_code'),
            street: document.getElementById('student_street'),
            number: document.getElementById('student_number'),
            complement: document.getElementById('student_complement'),
            neighborhood: document.getElementById('student_neighborhood'),
            city: document.getElementById('student_city'),
            state: document.getElementById('student_state'),
            emergency_contact_name: document.getElementById('student_emergency_contact_name'),
            emergency_contact_phone: document.getElementById('student_emergency_contact_phone'),
            emergency_contact_relationship: document.getElementById('student_emergency_contact_relationship'),
            source: document.getElementById('student_source'),
            status: document.getElementById('student_status'),
            admin_notes: document.getElementById('student_admin_notes'),
        };

        let currentId = null;

        function onlyDigits(value) {
            return String(value ?? '').replace(/\D/g, '');
        }

        function formatCpf(value) {
            return onlyDigits(value)
                .slice(0, 11)
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }

        cpfInput?.addEventListener('input', function () {
            cpfInput.value = formatCpf(cpfInput.value);
        });

        function showAvatar(url) {
            if (!url) {
                avatarPreviewWrap.classList.add('hidden');
                avatarPreviewWrap.classList.remove('flex');
                avatarPreview.removeAttribute('src');
                return;
            }

            avatarPreview.src = url;
            avatarPreviewWrap.classList.remove('hidden');
            avatarPreviewWrap.classList.add('flex');
        }

        avatarInput?.addEventListener('change', function () {
            const file = this.files && this.files[0];

            if (!file) {
                return;
            }

            if (removeAvatar) {
                removeAvatar.checked = false;
            }

            showAvatar(URL.createObjectURL(file));
        });

        function setAdditionalUnits(ids) {
            const selected = (ids ?? []).map(Number);

            form.querySelectorAll('input[name="additional_unit_ids[]"]').forEach(function (input) {
                input.checked = selected.includes(Number(input.value));
            });
        }

        window.openStudentModal = function (studentId = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            showAvatar(null);
            setAdditionalUnits([]);
            accessHint?.classList.add('hidden');
            if (accessHint) {
                accessHint.textContent = '';
            }
            currentId = studentId;

            if (studentId) {
                title.textContent = 'Editar aluno';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('students.update', ':id') }}`.replace(':id', studentId);

                fetch(`{{ route('students.edit-data', ':id') }}`.replace(':id', studentId), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar os dados do aluno.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        Object.entries(fields).forEach(function ([key, input]) {
                            if (input) {
                                input.value = data[key] ?? '';
                            }
                        });

                        primaryUnitSelect.value = data.primary_unit_id ?? '';
                        setAdditionalUnits(data.additional_unit_ids ?? []);
                        showAvatar(data.avatar_url);

                        if (data.has_access && accessHint) {
                            accessHint.textContent = 'Este aluno já possui acesso com o e-mail ' + (data.access_email ?? '') + '. Deixe a senha em branco para manter.';
                            accessHint.classList.remove('hidden');
                        }
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Novo aluno';
                submitText.textContent = 'Salvar aluno';
                methodInput.value = 'POST';
                form.action = '{{ route('students.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                fields.name.focus();
            }, 100);
        };

        window.closeStudentModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentId = null;
        };

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            errorBox.classList.add('hidden');
            submitBtn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, status: response.status, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok) {
                        if (result.status === 422 && result.data.errors) {
                            throw new Error(Object.values(result.data.errors).flat().join(' '));
                        }

                        throw new Error(result.data.message || 'Não foi possível salvar o aluno.');
                    }

                    alertSuccess(currentId ? 'Atualizado!' : 'Cadastrado!', result.data.message);
                    window.closeStudentModal();

                    if (typeof window.afterStudentSaved === 'function') {
                        window.afterStudentSaved(result.data.data);
                    } else {
                        setTimeout(function () {
                            window.location.reload();
                        }, 600);
                    }
                })
                .catch(function (error) {
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('hidden');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                });
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                window.closeStudentModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeStudentModal();
            }
        });
    })();
</script>
