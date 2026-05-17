@extends('admin.layouts.panel')

@section('title', 'Users')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>User directory</h2>
                    <p>Review account health, roles, verification, and region.</p>
                </div>
                <button type="button">Add user</button>
            </div>
            <div class="admin-filter-row">
                <button type="button" class="is-active">All</button>
                <button type="button">Buyers</button>
                <button type="button">Sellers</button>
                <button type="button">Flagged</button>
            </div>
            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user['name'] }}</td>
                                <td>{{ $user['email'] }}</td>
                                <td>{{ $user['role'] }}</td>
                                <td>{{ $user['country'] }}</td>
                                <td><span>{{ $user['status'] }}</span></td>
                                <td>{{ $user['joined'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Users pagination'])
        </article>

        <aside class="admin-panel admin-side-insights">
            <div class="admin-panel-head">
                <div>
                    <h2>Verification focus</h2>
                    <p>Highest priority account checks.</p>
                </div>
            </div>
            <ul>
                <li><strong>18</strong><span>Seller identity reviews</span></li>
                <li><strong>9</strong><span>Payout method changes</span></li>
                <li><strong>6</strong><span>Duplicate account signals</span></li>
            </ul>
        </aside>
    </section>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Seller verification pipeline</h2>
                    <p>Where account approvals are currently blocked.</p>
                </div>
            </div>
            <div class="admin-quality-bars">
                <span style="--value: 68%"><b>Identity submitted</b><em>68%</em></span>
                <span style="--value: 52%"><b>Portfolio complete</b><em>52%</em></span>
                <span style="--value: 81%"><b>Payout connected</b><em>81%</em></span>
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Account interventions</h2>
                    <p>Recommended follow-up actions.</p>
                </div>
            </div>
            <div class="admin-workflow-steps">
                <span><b>1</b><strong>Request missing ID</strong><small>18 sellers</small></span>
                <span><b>2</b><strong>Review flagged logins</strong><small>6 accounts</small></span>
                <span><b>3</b><strong>Welcome high-value buyers</strong><small>32 new buyers</small></span>
            </div>
        </article>
    </section>
@endsection
