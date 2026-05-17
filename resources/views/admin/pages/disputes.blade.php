@extends('admin.layouts.panel')

@section('title', 'Disputes')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Resolution queue</h2>
                    <p>Prioritize cases by urgency, owner, and buyer/seller risk.</p>
                </div>
                <button type="button">Assign cases</button>
            </div>
            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Case</th>
                            <th>Order</th>
                            <th>Reason</th>
                            <th>Owner</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($disputes as $dispute)
                            <tr>
                                <td>{{ $dispute['case'] }}</td>
                                <td>{{ $dispute['order'] }}</td>
                                <td>{{ $dispute['reason'] }}</td>
                                <td>{{ $dispute['owner'] }}</td>
                                <td><span>{{ $dispute['priority'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Disputes pagination'])
        </article>

        <aside class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Case playbook</h2>
                    <p>Suggested flow for support agents.</p>
                </div>
            </div>
            <ol class="admin-activity-list">
                <li>Check order requirements and delivery files.</li>
                <li>Request missing evidence from the correct party.</li>
                <li>Offer revision window before refund decisions.</li>
                <li>Escalate high-value cases to marketplace trust.</li>
            </ol>
        </aside>
    </section>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Resolution SLA</h2>
                    <p>Current response health for open cases.</p>
                </div>
            </div>
            <div class="admin-quality-bars">
                <span style="--value: 74%"><b>Responded within 12h</b><em>74%</em></span>
                <span style="--value: 46%"><b>Evidence complete</b><em>46%</em></span>
                <span style="--value: 62%"><b>Eligible for mediation</b><em>62%</em></span>
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Risk buckets</h2>
                    <p>Prioritize by buyer impact and order value.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                <article class="admin-mini-card"><div><strong>High-value refund risk</strong><p>3 cases above $500</p></div><b>Critical</b></article>
                <article class="admin-mini-card"><div><strong>Late delivery dispute</strong><p>9 active cases</p></div><b>High</b></article>
                <article class="admin-mini-card"><div><strong>Scope disagreement</strong><p>7 cases need evidence</p></div><b>Normal</b></article>
            </div>
        </article>
    </section>
@endsection
