@extends('admin.layouts.panel')

@section('title', 'Settings')

@section('panel')
    <section class="admin-page-grid">
        <article class="admin-panel admin-settings-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Marketplace settings</h2>
                    <p>Static settings template for future admin configuration.</p>
                </div>
                <button type="button">Save changes</button>
            </div>
            <div class="admin-settings-list">
                @foreach ($settings as $setting)
                    <label>
                        <span>
                            <strong>{{ $setting['label'] }}</strong>
                            <small>{{ $setting['description'] }}</small>
                        </span>
                        <input type="checkbox" @checked($setting['enabled'])>
                    </label>
                @endforeach
            </div>
        </article>

        <aside class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Admin credentials</h2>
                    <p>Temporary credentials are configured through environment values.</p>
                </div>
            </div>
            <div class="admin-config-list">
                <p><span>Name</span><strong>{{ config('admin.name') }}</strong></p>
                <p><span>Email</span><strong>{{ config('admin.email') }}</strong></p>
                <p><span>Password env</span><strong>ADMIN_PASSWORD</strong></p>
            </div>
        </aside>
    </section>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Policy guardrails</h2>
                    <p>Operational rules that should be backed by policies later.</p>
                </div>
            </div>
            <div class="admin-workflow-steps">
                <span><b>A</b><strong>Admin role permissions</strong><small>Separate finance, support, and catalog access</small></span>
                <span><b>L</b><strong>Audit logging</strong><small>Track settings and payout changes</small></span>
                <span><b>2</b><strong>Two-factor login</strong><small>Recommended before production</small></span>
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Environment checklist</h2>
                    <p>Values to replace before production use.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                <article class="admin-mini-card"><div><strong>ADMIN_PASSWORD</strong><p>Move away from demo password</p></div><b>Required</b></article>
                <article class="admin-mini-card"><div><strong>Admin middleware</strong><p>Replace session template gate</p></div><b>Required</b></article>
                <article class="admin-mini-card"><div><strong>Role policy map</strong><p>Define permissions per module</p></div><b>Next</b></article>
            </div>
        </article>
    </section>
@endsection
