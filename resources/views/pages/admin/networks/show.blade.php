@extends('layouts.app')

@section('title', 'Rede - '.$network->name)

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .view-tab {
            color: var(--color-text-secondary);
        }

        .view-tab.is-active {
            background-color: var(--color-cyan-neon);
            color: var(--color-bg-primary);
        }

        .smartgym-map-marker {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--marker-color);
            color: #090D18;
            border: 2px solid #090D18;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.35);
            font-size: 13px;
        }

        .leaflet-container {
            background-color: var(--color-bg-secondary);
            font-family: var(--font-sans);
        }

        .leaflet-popup-content-wrapper,
        .leaflet-popup-tip {
            background-color: var(--color-surface-elevated);
            color: var(--color-text-primary);
        }

        .leaflet-container a {
            color: var(--color-cyan-neon);
        }
    </style>
@endpush

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6 space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('admin.networks.index') }}"
               class="text-sm text-text-secondary hover:text-cyan transition-colors">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para redes
            </a>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-2xl font-bold truncate">{{ $network->name }}</h1>
                @if ($network->isActive())
                    <span class="badge badge-success">Ativo</span>
                @elseif ($network->status === 'inactive')
                    <span class="badge badge-secondary">Inativo</span>
                @elseif ($network->status === 'suspended')
                    <span class="badge badge-warning">Suspenso</span>
                @else
                    <span class="badge badge-info">Pendente</span>
                @endif
            </div>
            <p class="text-text-secondary mt-1">{{ $network->slug }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('networks.manage')
                <button type="button" onclick="openNetworkModal({{ $network->id }})" class="btn-outline">
                    <i class="fas fa-edit mr-2"></i>
                    Editar rede
                </button>
            @endcan
            <button type="button" onclick="openRegionModal()" class="btn-outline">
                <i class="fas fa-map-marked-alt mr-2"></i>
                Nova região
            </button>
            <button type="button" onclick="openUnitModal()" class="btn-primary">
                <i class="fas fa-building mr-2"></i>
                Nova unidade
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Network data --}}
        <div class="lg:col-span-2 card">
            <h2 class="font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-circle-info text-cyan"></i>
                Dados da rede
            </h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <dt class="text-text-secondary text-sm">Nome</dt>
                    <dd class="font-medium mt-1">{{ $network->name }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">CNPJ</dt>
                    <dd class="font-medium mt-1 font-mono">{{ $network->document ?? 'Não informado' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">Responsável</dt>
                    <dd class="font-medium mt-1">{{ $network->responsible_name ?? 'Não informado' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">CPF do responsável</dt>
                    <dd class="font-medium mt-1 font-mono">{{ $network->responsible_cpf ?? 'Não informado' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">E-mail do responsável</dt>
                    <dd class="font-medium mt-1">{{ $network->responsible_email ?? 'Não informado' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">Telefone do responsável</dt>
                    <dd class="font-medium mt-1">{{ $network->responsible_phone ?? 'Não informado' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-text-secondary text-sm">Endereço</dt>
                    <dd class="font-medium mt-1">
                        {{ $network->full_address !== '' ? $network->full_address : 'Não informado' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">Plano SaaS</dt>
                    <dd class="mt-1">
                        @if ($network->plan)
                            <span class="badge badge-info">{{ $network->plan->name }}</span>
                        @else
                            <span class="text-text-secondary">Sem plano associado</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">Criado em</dt>
                    <dd class="font-medium mt-1">{{ $network->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Summary --}}
        <div class="card">
            <h2 class="font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-chart-simple text-purple"></i>
                Resumo
            </h2>

            <div class="space-y-3">
                <div class="flex items-center justify-between rounded-lg bg-bg-secondary p-3">
                    <span class="flex items-center gap-2 text-sm text-text-secondary">
                        <i class="fas fa-map-marked-alt text-cyan"></i>
                        Regiões
                    </span>
                    <strong>{{ $regions->count() }}</strong>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-bg-secondary p-3">
                    <span class="flex items-center gap-2 text-sm text-text-secondary">
                        <i class="fas fa-building text-purple"></i>
                        Unidades
                    </span>
                    <strong>{{ $unitsCount }}</strong>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-bg-secondary p-3">
                    <span class="flex items-center gap-2 text-sm text-text-secondary">
                        <i class="fas fa-link-slash text-yellow"></i>
                        Unidades sem região
                    </span>
                    <strong>{{ $unlinkedUnits->count() }}</strong>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-bg-secondary p-3">
                    <span class="flex items-center gap-2 text-sm text-text-secondary">
                        <i class="fas fa-clock-rotate-left text-lime"></i>
                        Atualizado em
                    </span>
                    <span class="text-sm">{{ $network->updated_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Visualization: org chart + map --}}
    <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h2 class="font-semibold flex items-center gap-2">
                    <i class="fas fa-diagram-project text-cyan"></i>
                    Visualização da estrutura
                </h2>
                <p class="text-sm text-text-secondary mt-1">
                    Organograma e mapa das regiões e unidades desta rede.
                </p>
            </div>

            <div class="inline-flex rounded-lg border border-custom bg-bg-secondary p-1">
                <button type="button" id="viewOrgBtn" class="view-tab is-active px-3 py-1.5 text-sm rounded-md transition-colors">
                    <i class="fas fa-sitemap mr-1"></i>
                    Organograma
                </button>
                <button type="button" id="viewMapBtn" class="view-tab px-3 py-1.5 text-sm rounded-md transition-colors">
                    <i class="fas fa-map-location-dot mr-1"></i>
                    Mapa
                </button>
            </div>
        </div>

        <div id="orgChartPanel">
            <div id="orgChart"
                 class="rounded-xl border border-custom"
                 style="height: 520px; background-color: var(--color-bg-secondary);"></div>
        </div>

        <div id="mapPanel" class="hidden">
            <div id="networkMap"
                 class="rounded-xl border border-custom"
                 style="height: 520px;"></div>
            <p id="mapStatus" class="text-xs text-text-secondary mt-2">Carregando mapa...</p>
        </div>
    </div>

    {{-- Organizational structure --}}
    <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h2 class="font-semibold flex items-center gap-2">
                    <i class="fas fa-sitemap text-lime"></i>
                    Estrutura organizacional
                </h2>
                <p class="text-sm text-text-secondary mt-1">
                    Unidades agrupadas por região. As unidades sem vínculo aparecem primeiro.
                </p>
            </div>
        </div>

        {{-- Units without region --}}
        <div class="rounded-xl border border-dashed border-custom p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: rgba(255, 179, 0, 0.15);">
                    <i class="fas fa-link-slash" style="color: var(--color-yellow);"></i>
                </div>
                <div>
                    <h3 class="font-medium">Unidades sem região</h3>
                    <p class="text-xs text-text-secondary">{{ $unlinkedUnits->count() }} unidade(s) sem vínculo regional</p>
                </div>
            </div>

            @if ($unlinkedUnits->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach ($unlinkedUnits as $unit)
                        <div class="rounded-lg border border-custom bg-bg-secondary p-4">
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-medium truncate">{{ $unit->name }}</p>
                                @if ($unit->isActive())
                                    <span class="badge badge-success whitespace-nowrap">Ativa</span>
                                @else
                                    <span class="badge badge-secondary whitespace-nowrap">Inativa</span>
                                @endif
                            </div>
                            <p class="text-xs text-text-secondary mt-1 font-mono">{{ $unit->identifier }}</p>
                            <p class="text-xs text-text-secondary mt-2">
                                <i class="fas fa-location-dot mr-1"></i>
                                {{ $unit->city ? $unit->city.($unit->state ? '/'.$unit->state : '') : 'Endereço não informado' }}
                            </p>
                            @if ($unit->phone)
                                <p class="text-xs text-text-secondary mt-1">
                                    <i class="fas fa-phone mr-1"></i>
                                    {{ $unit->phone }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-text-secondary">
                    Todas as unidades desta rede estão vinculadas a uma região.
                </p>
            @endif
        </div>

        {{-- Regions --}}
        <div class="mt-5 space-y-4">
            @forelse ($regions as $region)
                <div class="rounded-xl border border-custom overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-bg-secondary px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(0, 229, 255, 0.15);">
                                <i class="fas fa-map-marked-alt" style="color: var(--color-cyan-neon);"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-medium truncate">{{ $region->name }}</h3>
                                    @if (! $region->isActive())
                                        <span class="badge badge-secondary">Inativa</span>
                                    @endif
                                </div>
                                @if ($region->description)
                                    <p class="text-xs text-text-secondary truncate">{{ $region->description }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="badge badge-info whitespace-nowrap self-start sm:self-auto">
                            {{ $region->units_count }} unidade(s)
                        </span>
                    </div>

                    @if ($region->units->count() > 0)
                        <div class="divide-y" style="border-color: var(--color-border);">
                            @foreach ($region->units as $unit)
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <i class="fas fa-building text-purple"></i>
                                        <div class="min-w-0">
                                            <p class="font-medium truncate">{{ $unit->name }}</p>
                                            <p class="text-xs text-text-secondary font-mono">{{ $unit->identifier }}</p>
                                        </div>
                                    </div>
                                    <div class="text-xs text-text-secondary sm:text-right">
                                        <p>
                                            <i class="fas fa-location-dot mr-1"></i>
                                            {{ $unit->city ? $unit->city.($unit->state ? '/'.$unit->state : '') : 'Endereço não informado' }}
                                        </p>
                                        @if ($unit->phone)
                                            <p class="mt-1"><i class="fas fa-phone mr-1"></i>{{ $unit->phone }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="px-4 py-4 text-sm text-text-secondary">
                            Nenhuma unidade vinculada a esta região.
                        </p>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-custom p-6 text-center">
                    <i class="fas fa-map-marked-alt text-3xl text-text-secondary mb-2"></i>
                    <p class="font-medium">Nenhuma região cadastrada</p>
                    <p class="text-sm text-text-secondary mt-1">
                        Cadastre a primeira região para organizar as unidades desta rede.
                    </p>
                    <button type="button" onclick="openRegionModal()" class="btn-outline mt-4">
                        <i class="fas fa-plus mr-2"></i>
                        Cadastrar região
                    </button>
                </div>
            @endforelse
        </div>
    </div>
</div>

@include('pages.admin.networks._modal')
@include('pages.admin.regions._modal', ['network' => $network])
@include('pages.admin.units._modal', ['network' => $network])

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/vis-network@9.1.9/standalone/umd/vis-network.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            const structure = @json($structure);
            const unitBaseUrl = '{{ url('admin/units') }}';
            const orgPanel = document.getElementById('orgChartPanel');
            const mapPanel = document.getElementById('mapPanel');
            const orgButton = document.getElementById('viewOrgBtn');
            const mapButton = document.getElementById('viewMapBtn');
            const mapStatus = document.getElementById('mapStatus');

            function cssVar(name, fallback) {
                const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
                return value || fallback;
            }

            const colors = {
                cyan: cssVar('--color-cyan-neon', '#00E5FF'),
                purple: cssVar('--color-purple-neon', '#A855F7'),
                surface: cssVar('--color-surface', '#192337'),
                surfaceElevated: cssVar('--color-surface-elevated', '#222F46'),
                border: cssVar('--color-border', '#2A3850'),
                text: cssVar('--color-text-primary', '#F1F5F9'),
            };

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }

            function unitTooltip(unit) {
                const region = unit.region ? `<br>Região: ${escapeHtml(unit.region)}` : '<br>Sem região';
                const address = unit.address ? `<br>${escapeHtml(unit.address)}` : '';
                const phone = unit.phone ? `<br>${escapeHtml(unit.phone)}` : '';

                return `<strong>${escapeHtml(unit.name)}</strong><br>${escapeHtml(unit.identifier)}${region}${address}${phone}`;
            }

            function buildOrgChart() {
                if (typeof vis === 'undefined' || !orgPanel) {
                    return;
                }

                const nodes = [];
                const edges = [];

                nodes.push({
                    id: 'network',
                    label: structure.name,
                    group: 'network',
                    level: 0,
                    title: 'Rede',
                });

                function addUnit(unit, parentId, level) {
                    const nodeId = `unit-${unit.id}`;

                    nodes.push({
                        id: nodeId,
                        label: `${unit.name}\n${unit.identifier}`,
                        group: unit.status === 'active' ? 'unit' : 'unitInactive',
                        level: level,
                        title: unitTooltip(unit),
                    });

                    edges.push({ from: parentId, to: nodeId });
                }

                (structure.regions || []).forEach(function (region) {
                    const regionNodeId = `region-${region.id}`;

                    nodes.push({
                        id: regionNodeId,
                        label: region.name,
                        group: 'region',
                        level: 1,
                        title: 'Região',
                    });

                    edges.push({ from: 'network', to: regionNodeId });

                    (region.units || []).forEach(function (unit) {
                        addUnit(unit, regionNodeId, 2);
                    });
                });

                (structure.unlinkedUnits || []).forEach(function (unit) {
                    addUnit(unit, 'network', 1);
                });

                const networkChart = new vis.Network(
                    document.getElementById('orgChart'),
                    {
                        nodes: new vis.DataSet(nodes),
                        edges: new vis.DataSet(edges),
                    },
                    {
                        layout: {
                            hierarchical: {
                                direction: 'UD',
                                sortMethod: 'directed',
                                levelSeparation: 140,
                                nodeSpacing: 150,
                                treeSpacing: 190,
                            },
                        },
                        physics: false,
                        interaction: {
                            hover: true,
                            dragNodes: false,
                            dragView: true,
                            zoomView: true,
                            tooltipDelay: 120,
                        },
                        groups: {
                            network: {
                                shape: 'box',
                                color: { background: colors.cyan, border: colors.cyan },
                                font: { color: '#090D18', face: 'Instrument Sans', size: 16, bold: true },
                                borderWidth: 2,
                                margin: 14,
                                widthConstraint: { maximum: 240 },
                            },
                            region: {
                                shape: 'box',
                                color: { background: colors.surface, border: colors.purple },
                                font: { color: colors.text, face: 'Instrument Sans', size: 14, bold: true },
                                borderWidth: 2,
                                margin: 12,
                                widthConstraint: { maximum: 240 },
                            },
                            unit: {
                                shape: 'box',
                                color: { background: colors.surfaceElevated, border: colors.border },
                                font: { color: colors.text, face: 'Instrument Sans', size: 12 },
                                margin: 10,
                                widthConstraint: { maximum: 220 },
                            },
                            unitInactive: {
                                shape: 'box',
                                color: { background: colors.surfaceElevated, border: colors.border },
                                font: { color: colors.text, face: 'Instrument Sans', size: 12 },
                                margin: 10,
                                widthConstraint: { maximum: 220 },
                                opacity: 0.55,
                            },
                        },
                        edges: {
                            smooth: { type: 'cubicBezier', forceDirection: 'vertical', roundness: 0.4 },
                            color: { color: colors.border, highlight: colors.cyan },
                            width: 1.5,
                            arrows: { to: { enabled: true, scaleFactor: 0.6 } },
                        },
                    }
                );

                networkChart.on('doubleClick', function (params) {
                    if (!params.nodes.length) {
                        return;
                    }

                    const nodeId = String(params.nodes[0]);

                    if (nodeId.startsWith('unit-')) {
                        window.location.href = `${unitBaseUrl}/${nodeId.replace('unit-', '')}`;
                    }
                });
            }

            let networkMap = null;
            let mapStarted = false;
            const geocodeCache = new Map();

            const regionPalette = ['#00E5FF', '#A855F7', '#C6FF00', '#FFB300', '#FF3864', '#00E676'];

            function regionColors() {
                const map = new Map();

                (structure.regions || []).forEach(function (region, index) {
                    map.set(region.name, regionPalette[index % regionPalette.length]);
                });

                return map;
            }

            function delay(milliseconds) {
                return new Promise(function (resolve) {
                    setTimeout(resolve, milliseconds);
                });
            }

            async function geocode(query) {
                if (!query) {
                    return null;
                }

                if (geocodeCache.has(query)) {
                    return geocodeCache.get(query);
                }

                const storageKey = `smartgym:geocode:v1:${query}`;

                try {
                    const saved = localStorage.getItem(storageKey);

                    if (saved) {
                        const parsed = JSON.parse(saved);
                        geocodeCache.set(query, parsed);

                        return parsed;
                    }
                } catch (error) {
                    // Ignore storage errors.
                }

                try {
                    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(query)}`;
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });

                    if (!response.ok) {
                        return null;
                    }

                    const data = await response.json();

                    if (!Array.isArray(data) || data.length === 0) {
                        return null;
                    }

                    const point = {
                        lat: parseFloat(data[0].lat),
                        lng: parseFloat(data[0].lon),
                    };

                    geocodeCache.set(query, point);

                    try {
                        localStorage.setItem(storageKey, JSON.stringify(point));
                    } catch (error) {
                        // Ignore storage errors.
                    }

                    return point;
                } catch (error) {
                    return null;
                }
            }

            function unitIcon(color) {
                return L.divIcon({
                    className: '',
                    html: `<div class="smartgym-map-marker" style="--marker-color: ${color};"><i class="fas fa-dumbbell"></i></div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15],
                    popupAnchor: [0, -16],
                });
            }

            function unitPopup(unit, color) {
                const address = unit.address ? `<br><span style="color: var(--color-text-secondary);">${escapeHtml(unit.address)}</span>` : '';
                const phone = unit.phone ? `<br><span style="color: var(--color-text-secondary);">${escapeHtml(unit.phone)}</span>` : '';
                const region = unit.region ? escapeHtml(unit.region) : 'Sem região';

                return `<div style="font-size: 13px;"><strong>${escapeHtml(unit.name)}</strong><br>${escapeHtml(unit.identifier)}<br><span style="color: ${color};">${region}</span>${address}${phone}</div>`;
            }

            async function plotUnitsOnMap() {
                if (!networkMap || mapStarted) {
                    return;
                }

                mapStarted = true;

                const units = [];
                const colorsByRegion = regionColors();

                (structure.regions || []).forEach(function (region) {
                    (region.units || []).forEach(function (unit) {
                        units.push(unit);
                    });
                });

                (structure.unlinkedUnits || []).forEach(function (unit) {
                    units.push(unit);
                });

                if (!units.length) {
                    mapStatus.textContent = 'Nenhuma unidade cadastrada para exibir no mapa.';
                    return;
                }

                const bounds = L.latLngBounds([]);
                const regionPoints = new Map();
                let mapped = 0;

                for (let index = 0; index < units.length; index++) {
                    const unit = units[index];
                    mapStatus.textContent = `Localizando unidades... ${index + 1}/${units.length}`;

                    const query = [unit.address, unit.city, unit.state, 'Brasil'].filter(Boolean).join(', ');
                    const point = await geocode(query);

                    if (point) {
                        mapped++;

                        const color = unit.region ? (colorsByRegion.get(unit.region) || '#A6B0C3') : '#A6B0C3';

                        L.marker([point.lat, point.lng], { icon: unitIcon(color) })
                            .bindPopup(unitPopup(unit, color))
                            .addTo(networkMap);

                        bounds.extend([point.lat, point.lng]);

                        if (unit.region) {
                            const points = regionPoints.get(unit.region) || [];
                            points.push(point);
                            regionPoints.set(unit.region, points);
                        }
                    }

                    if (index < units.length - 1) {
                        await delay(1100);
                    }
                }

                regionPoints.forEach(function (points, name) {
                    const lat = points.reduce(function (carry, point) { return carry + point.lat; }, 0) / points.length;
                    const lng = points.reduce(function (carry, point) { return carry + point.lng; }, 0) / points.length;
                    const color = colorsByRegion.get(name) || '#A6B0C3';

                    L.circleMarker([lat, lng], {
                        radius: 14,
                        color: color,
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 0.15,
                    })
                        .bindPopup(`<strong>Região ${escapeHtml(name)}</strong><br>${points.length} unidade(s) localizada(s)`)
                        .addTo(networkMap);
                });

                if (bounds.isValid()) {
                    networkMap.fitBounds(bounds.pad(0.25));
                }

                mapStatus.textContent = `${mapped} de ${units.length} unidade(s) localizada(s) no mapa.`;
            }

            function initMap() {
                if (typeof L === 'undefined') {
                    return;
                }

                if (networkMap) {
                    setTimeout(function () {
                        networkMap.invalidateSize();
                    }, 120);

                    return;
                }

                networkMap = L.map('networkMap', { scrollWheelZoom: false }).setView([-15.793889, -47.882778], 4);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
                }).addTo(networkMap);

                setTimeout(function () {
                    networkMap.invalidateSize();
                }, 120);

                plotUnitsOnMap();
            }

            function activateView(view) {
                const showMap = view === 'map';

                orgPanel?.classList.toggle('hidden', showMap);
                mapPanel?.classList.toggle('hidden', !showMap);
                orgButton?.classList.toggle('is-active', !showMap);
                mapButton?.classList.toggle('is-active', showMap);

                if (showMap) {
                    initMap();
                }
            }

            orgButton?.addEventListener('click', function () {
                activateView('org');
            });

            mapButton?.addEventListener('click', function () {
                activateView('map');
            });

            buildOrgChart();
        })();
    </script>
@endpush
@endsection
