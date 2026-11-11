<div class="space-y-4">
    @foreach ($policyDomains as $domainKey => $domain)
        <section class="rounded-xl border border-custom bg-bg-secondary p-4 space-y-4">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg"
                     style="background: color-mix(in srgb, var(--color-primary) 12%, transparent);">
                    <i class="fas {{ $domain['icon'] }} text-cyan"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-semibold">{{ $domain['title'] }}</h3>
                    <p class="mt-1 text-xs text-text-secondary">{{ $domain['description'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($domain['fields'] as $fieldKey => $field)
                    @php($errorKey = "policies.{$domainKey}.{$fieldKey}")
                    <div class="{{ $field['type'] === 'toggle' ? 'sm:col-span-2' : '' }}">
                        @if ($field['type'] === 'select')
                            <label for="policy_{{ $domainKey }}_{{ $fieldKey }}" class="label-field text-sm">
                                {{ $field['label'] }}
                            </label>
                            <select id="policy_{{ $domainKey }}_{{ $fieldKey }}"
                                    name="policies[{{ $domainKey }}][{{ $fieldKey }}]"
                                    class="input-field">
                                @foreach ($field['options'] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}"
                                            @selected(old("policies.{$domainKey}.{$fieldKey}", $policyValues[$domainKey][$fieldKey]) === $optionValue)>
                                        {{ $optionLabel }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($field['help'])
                                <p class="mt-1 text-xs text-text-secondary">{{ $field['help'] }}</p>
                            @endif
                        @else
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-custom bg-surface p-3 transition-colors hover:border-cyan">
                                <input type="hidden"
                                       name="policies[{{ $domainKey }}][{{ $fieldKey }}]"
                                       value="0">
                                <input type="checkbox"
                                       id="policy_{{ $domainKey }}_{{ $fieldKey }}"
                                       name="policies[{{ $domainKey }}][{{ $fieldKey }}]"
                                       value="1"
                                       class="mt-0.5 flex-shrink-0 rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan"
                                       @checked(filter_var(old("policies.{$domainKey}.{$fieldKey}", $policyValues[$domainKey][$fieldKey]), FILTER_VALIDATE_BOOL))>
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium">{{ $field['label'] }}</span>
                                    @if ($field['help'])
                                        <span class="mt-0.5 block text-xs text-text-secondary">{{ $field['help'] }}</span>
                                    @endif
                                </span>
                            </label>
                        @endif

                        @error($errorKey)
                            <p class="mt-1 text-xs text-red">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
