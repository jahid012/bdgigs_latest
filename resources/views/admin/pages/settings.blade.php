@extends('admin.layouts.panel')

@section('title', 'Settings')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-settings-layout">
        <form class="admin-settings-main" method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            <article class="admin-panel admin-settings-intro">
                <div>
                    <p class="admin-eyebrow">Configuration center</p>
                    <h2>Control the marketplace rules that affect revenue, trust, and delivery.</h2>
                    <p>
                        These controls are stored in the database and read through cache by the platform settings helpers.
                    </p>
                </div>
                @can('settings.update')
                    <button type="submit">Save changes</button>
                @else
                    <button type="button" disabled>View only</button>
                @endcan
            </article>

            @foreach ($settingGroups as $group)
                <article class="admin-panel admin-settings-panel">
                    <div class="admin-panel-head">
                        <div>
                            <h2>{{ $group['title'] }}</h2>
                            <p>{{ $group['description'] }}</p>
                        </div>
                    </div>

                    <div class="admin-setting-rows">
                        @foreach ($group['settings'] as $setting)
                            @php
                                $fieldId = 'setting-' . $loop->parent->index . '-' . $loop->index;
                                $type = $setting['type'] ?? 'text';
                            @endphp

                            @if ($type === 'toggle')
                                <label class="admin-setting-row admin-setting-row-toggle" for="{{ $fieldId }}">
                                    <span class="admin-setting-copy">
                                        <strong>{{ $setting['label'] }}</strong>
                                        <small>{{ $setting['description'] }}</small>
                                    </span>
                                    <span class="admin-setting-switch">
                                        <input
                                            id="{{ $fieldId }}"
                                            name="settings[{{ $setting['name'] }}]"
                                            value="1"
                                            type="checkbox"
                                            @checked($setting['value'] ?? false)
                                            @disabled(! auth()->user()?->can('settings.update'))
                                        >
                                        <i></i>
                                    </span>
                                </label>
                            @else
                                <label class="admin-setting-row" for="{{ $fieldId }}">
                                    <span class="admin-setting-copy">
                                        <strong>{{ $setting['label'] }}</strong>
                                        <small>{{ $setting['description'] }}</small>
                                    </span>
                                    <span class="admin-setting-control">
                                        @if ($type === 'select')
                                            <select id="{{ $fieldId }}" name="settings[{{ $setting['name'] }}]" @disabled(! auth()->user()?->can('settings.update'))>
                                                @foreach (($setting['options'] ?? []) as $option)
                                                    <option value="{{ $option }}" @selected(($setting['value'] ?? '') === $option)>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($type === 'textarea')
                                            <textarea
                                                id="{{ $fieldId }}"
                                                name="settings[{{ $setting['name'] }}]"
                                                rows="3"
                                                @disabled(! auth()->user()?->can('settings.update'))
                                            >{{ $setting['value'] ?? '' }}</textarea>
                                        @else
                                            <span class="admin-input-shell">
                                                @isset($setting['prefix'])
                                                    <em>{{ $setting['prefix'] }}</em>
                                                @endisset
                                                <input
                                                    id="{{ $fieldId }}"
                                                    name="settings[{{ $setting['name'] }}]"
                                                    type="{{ $type === 'number' ? 'number' : 'text' }}"
                                                    value="{{ $setting['value'] ?? '' }}"
                                                    @disabled(! auth()->user()?->can('settings.update'))
                                                >
                                                @isset($setting['suffix'])
                                                    <em>{{ $setting['suffix'] }}</em>
                                                @endisset
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </article>
            @endforeach
        </form>

        <aside class="admin-settings-sidebar">
            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Admin credentials</h2>
                        <p>Temporary credentials are configured through environment values.</p>
                    </div>
                </div>
                <div class="admin-config-list">
                    @foreach ($settingsSidebar['systemInfo'] as $item)
                        <p><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong></p>
                    @endforeach
                </div>
            </article>

            <article class="admin-panel admin-side-insights">
                <div class="admin-panel-head">
                    <div>
                        <h2>Pending reviews</h2>
                        <p>Queues affected by these settings.</p>
                    </div>
                </div>
                <ul>
                    @foreach ($settingsSidebar['reviewQueue'] as $item)
                        <li><strong>{{ $item['value'] }}</strong><span>{{ $item['label'] }}</span></li>
                    @endforeach
                </ul>
            </article>

            @can('roles.manage')
                <article class="admin-panel admin-access-shortcut">
                    <div>
                        <p class="admin-eyebrow">Security</p>
                        <h2>Access control</h2>
                        <p>Manage staff roles, sensitive permissions, and admin module visibility.</p>
                    </div>
                    <div class="admin-access-shortcut-actions">
                        <a class="admin-panel-link" href="{{ route('admin.roles') }}">Open role manager</a>
                        <a class="admin-panel-link" href="{{ route('admin.roles.users') }}">Find users</a>
                    </div>
                </article>
            @endcan

            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Production checklist</h2>
                        <p>Recommended before these settings become live.</p>
                    </div>
                </div>
                <div class="admin-card-list compact">
                    @foreach ($settingsSidebar['checklist'] as $item)
                        <article class="admin-mini-card">
                            <div>
                                <strong>{{ $item['label'] }}</strong>
                                <p>Configuration readiness</p>
                            </div>
                            <b>{{ $item['status'] }}</b>
                        </article>
                    @endforeach
                </div>
            </article>
        </aside>
    </section>
@endsection
