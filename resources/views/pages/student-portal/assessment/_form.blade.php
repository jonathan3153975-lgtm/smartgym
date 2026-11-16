        @php
    $parq = old('parq_answers', $assessment?->parq_answers ?? []);
@endphp

{{-- Objetivo e rotina --}}
        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-bullseye text-cyan"></i>
                Objetivo e rotina
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="goal" class="label-field">Objetivo principal <span class="text-red">*</span></label>
                    <select id="goal" name="goal" required class="input-field">
                        <option value="">Selecione</option>
                        @foreach (\App\Models\StudentAssessment::GOALS as $value => $label)
                            <option value="{{ $value }}" @selected(old('goal', $assessment?->goal) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('goal')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="experience_level" class="label-field">Experiência com treino <span class="text-red">*</span></label>
                    <select id="experience_level" name="experience_level" required class="input-field">
                        <option value="">Selecione</option>
                        @foreach (\App\Models\StudentAssessment::EXPERIENCE_LEVELS as $value => $label)
                            <option value="{{ $value }}" @selected(old('experience_level', $assessment?->experience_level) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('experience_level')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="training_frequency" class="label-field">Quantos dias por semana pretende treinar? <span class="text-red">*</span></label>
                    <input type="number" id="training_frequency" name="training_frequency" min="1" max="7" required
                           class="input-field" value="{{ old('training_frequency', $assessment?->training_frequency) }}">
                    @error('training_frequency')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="goal_notes" class="label-field">Detalhes do objetivo</label>
                    <textarea id="goal_notes" name="goal_notes" rows="3" maxlength="2000" class="input-field"
                              placeholder="Ex.: perder 5 kg, ganhar resistência para corrida, melhorar postura...">{{ old('goal_notes', $assessment?->goal_notes) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Saúde --}}
        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-heart-pulse text-cyan"></i>
                Histórico de saúde
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="medical_conditions" class="label-field">Doenças ou condições de saúde</label>
                    <textarea id="medical_conditions" name="medical_conditions" rows="2" maxlength="2000" class="input-field"
                              placeholder="Ex.: hipertensão, diabetes, asma...">{{ old('medical_conditions', $assessment?->medical_conditions) }}</textarea>
                </div>

                <div>
                    <label for="medications" class="label-field">Medicamentos em uso</label>
                    <textarea id="medications" name="medications" rows="2" maxlength="2000" class="input-field"
                              placeholder="Ex.: losartana 50 mg">{{ old('medications', $assessment?->medications) }}</textarea>
                </div>

                <div>
                    <label for="injuries" class="label-field">Lesões</label>
                    <textarea id="injuries" name="injuries" rows="2" maxlength="2000" class="input-field"
                              placeholder="Ex.: lesão no joelho direito">{{ old('injuries', $assessment?->injuries) }}</textarea>
                </div>

                <div>
                    <label for="surgeries" class="label-field">Cirurgias</label>
                    <textarea id="surgeries" name="surgeries" rows="2" maxlength="2000" class="input-field"
                              placeholder="Ex.: cirurgia de menisco em 2021">{{ old('surgeries', $assessment?->surgeries) }}</textarea>
                </div>

                <div>
                    <label for="alcohol_consumption" class="label-field">Consumo de álcool</label>
                    <select id="alcohol_consumption" name="alcohol_consumption" class="input-field">
                        <option value="">Não informar</option>
                        @foreach (\App\Models\StudentAssessment::ALCOHOL_CONSUMPTION as $value => $label)
                            <option value="{{ $value }}" @selected(old('alcohol_consumption', $assessment?->alcohol_consumption) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sleep_hours" class="label-field">Horas de sono por noite</label>
                    <input type="number" id="sleep_hours" name="sleep_hours" min="1" max="16" class="input-field"
                           value="{{ old('sleep_hours', $assessment?->sleep_hours) }}">
                </div>

                <div>
                    <label for="stress_level" class="label-field">Nível de estresse percebido</label>
                    <select id="stress_level" name="stress_level" class="input-field">
                        <option value="">Não informar</option>
                        @for ($level = 1; $level <= 5; $level++)
                            <option value="{{ $level }}" @selected((string) old('stress_level', $assessment?->stress_level) === (string) $level)>
                                {{ $level }} - {{ ['Muito baixo', 'Baixo', 'Moderado', 'Alto', 'Muito alto'][$level - 1] }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="flex items-end">
                    <input type="hidden" name="smoker" value="0">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" id="smoker" name="smoker" value="1" @checked(old('smoker', $assessment?->smoker))>
                        <span class="text-sm">Fumo atualmente</span>
                    </label>
                </div>
            </div>
        </section>

        {{-- PAR-Q --}}
        <section class="card space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-clipboard-question text-cyan"></i>
                        Questionário de prontidão para atividade física
                    </h2>
                    <p class="mt-1 text-xs text-text-secondary">
                        Se você responder "sim" a alguma pergunta, recomendamos procurar orientação médica antes de iniciar.
                    </p>
                </div>
                <span class="badge badge-info self-start sm:self-auto">Obrigatório</span>
            </div>

            <div class="space-y-3">
                @foreach (\App\Models\StudentAssessment::PARQ_QUESTIONS as $key => $question)
                    @php($answer = filter_var($parq[$key] ?? false, FILTER_VALIDATE_BOOL))
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <p class="text-sm font-medium">{{ $question }}</p>
                        <div class="mt-2 flex gap-4">
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input type="radio" name="parq_answers[{{ $key }}]" value="1" @checked($answer)>
                                Sim
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input type="radio" name="parq_answers[{{ $key }}]" value="0" @checked(! $answer)>
                                Não
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            @error('parq_answers')
                <p class="text-sm text-red">{{ $message }}</p>
            @enderror
        </section>

        {{-- Medidas --}}
        <section class="card space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-ruler text-cyan"></i>
                        Medidas corporais
                    </h2>
                    <p class="mt-1 text-xs text-text-secondary">Opcional, mas ajuda no acompanhamento da evolução.</p>
                </div>
                <span class="badge badge-secondary self-start sm:self-auto">Opcional</span>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'weight_kg' => ['label' => 'Peso (kg)', 'decimals' => 3],
                    'height_cm' => ['label' => 'Altura', 'decimals' => 2],
                    'body_fat_percent' => ['label' => 'Gordura (%)', 'decimals' => 2],
                    'waist_cm' => ['label' => 'Cintura', 'decimals' => 2],
                    'hip_cm' => ['label' => 'Quadril', 'decimals' => 2],
                    'chest_cm' => ['label' => 'Tórax', 'decimals' => 2],
                    'arm_cm' => ['label' => 'Braço', 'decimals' => 2],
                    'thigh_cm' => ['label' => 'Coxa', 'decimals' => 2],
                ] as $field => $config)
                    <div>
                        <label for="{{ $field }}" class="label-field">{{ $config['label'] }}</label>
                        <input type="text" inputmode="decimal" maxlength="12"
                               id="{{ $field }}" name="{{ $field }}" class="input-field"
                               data-decimal-input="{{ $config['decimals'] }}"
                               placeholder="{{ $config['decimals'] === 3 ? '0,000' : '0,00' }}"
                               value="{{ old($field, $assessment?->{$field}) }}">
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Emergency contact --}}
        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-address-book text-cyan"></i>
                Contato de emergência
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="emergency_contact_name" class="label-field">Nome <span class="text-red">*</span></label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" required maxlength="255"
                           class="input-field"
                           value="{{ old('emergency_contact_name', $assessment?->emergency_contact_name ?? $student->emergency_contact_name) }}">
                </div>

                <div>
                    <label for="emergency_contact_phone" class="label-field">Telefone <span class="text-red">*</span></label>
                    <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" required maxlength="20"
                           class="input-field" data-phone
                           placeholder="(00) 00000-0000"
                           value="{{ old('emergency_contact_phone', $assessment?->emergency_contact_phone ?? $student->emergency_contact_phone) }}">
                </div>
            </div>
        </section>

        {{-- Observations and consent --}}
        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-notes-medical text-cyan"></i>
                Observações e consentimento
            </h2>

            <div>
                <label for="observations" class="label-field">Alguma informação adicional?</label>
                <textarea id="observations" name="observations" rows="3" maxlength="4000" class="input-field"
                          placeholder="Preferências, limitações, histórico de treino...">{{ old('observations', $assessment?->observations) }}</textarea>
            </div>

            <div class="rounded-xl border border-custom bg-bg-secondary p-4">
                <input type="hidden" name="consent" value="0">
                <label class="flex cursor-pointer items-start gap-2 text-sm">
                    <input type="checkbox" name="consent" value="1" required @checked(old('consent', $assessment?->consent))>
                    <span>
                        Autorizo o uso destas informações pela equipe da unidade para fins de avaliação física,
                        prescrição de treino e acompanhamento. <span class="text-red">*</span>
                    </span>
                </label>
                @error('consent')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>
        </section>

