<x-app-layout>
    <x-slot name="header">
        <div style="display:flex;flex-direction:column">
            <div style="font-size:17px;font-weight:800;color:var(--text-main)" class="topbar-title">Intelligence Dashboard</div>
            <div style="font-size:11.5px;color:var(--text-dim)" class="topbar-sub">Analisis Antrian Real-time · AI Decision Engine</div>
        </div>
    </x-slot>

    <style>
        .iq-grid-5  { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; }
        .iq-grid-32 { display:grid; grid-template-columns:2fr 1fr; gap:16px; }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            transition: box-shadow .2s;
        }
        .card-hdr {
            padding: 16px 20px 14px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-title { font-size:14px; font-weight:700; color:var(--text-main); }
        .card-sub   { font-size:11.5px; color:var(--text-dim); margin-top:2px; }
        /* STAT */
        .iq-stat { padding:16px 18px; }
        .iq-stat-lbl { font-size:12px; color:var(--text-dim); }
        .iq-stat-val { font-size:24px; font-weight:800; margin-top:4px; }
        /* TABLE */
        .iq-table { width:100%; border-collapse:collapse; }
        .iq-table th {
            text-align:left; padding:10px 16px;
            font-size:11px; font-weight:600; text-transform:uppercase;
            letter-spacing:.05em; color:var(--text-dim);
            border-bottom:1px solid var(--border);
            background:var(--bg-base);
        }
        .iq-table td { padding:12px 16px; border-bottom:1px solid var(--border); font-size:13px; color:var(--text-main); }
        .iq-table tr:last-child td { border-bottom:none; }
        .iq-table tr:hover td { background:var(--bg-hover); }
        /* BADGES */
        .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:600; }
        .badge-high   { background:rgba(239,68,68,0.12);  color:#ef4444; }
        .badge-medium { background:rgba(245,158,11,0.12); color:#f59e0b; }
        .badge-low    { background:rgba(16,185,129,0.12); color:#10b981; }
        .badge-default{ background:var(--bg-hover);       color:var(--text-muted); }
        .badge-busy   { background:rgba(239,68,68,0.12);  color:#ef4444;  border:1px solid rgba(239,68,68,0.25); }
        .badge-normal { background:rgba(16,185,129,0.12); color:#10b981;  border:1px solid rgba(16,185,129,0.25); }
        .badge-slow   { background:rgba(245,158,11,0.12); color:#f59e0b;  border:1px solid rgba(245,158,11,0.25); }
        .btn-status {
            border: 1px solid var(--border);
            background: var(--bg-base);
            color: var(--text-main);
            border-radius: 8px;
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-status:hover { background: var(--bg-hover); }
        .btn-status-done { border-color: rgba(16,185,129,0.4); color: #10b981; }
        /* AI ENGINE CARD */
        .engine-card {
            background:linear-gradient(135deg,var(--accent),#4f46e5);
            border-radius:14px; padding:18px; color:#fff;
        }
        .engine-bar-track { background:rgba(255,255,255,0.15); border-radius:4px; height:5px; }
        .engine-bar-fill  { border-radius:4px; height:100%; }
    </style>

    <div style="padding:24px; display:flex; flex-direction:column; gap:18px">

        {{-- ERROR --}}
        @if ($errorMessage)
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:14px 18px;display:flex;gap:12px;align-items:flex-start">
            <span style="font-size:18px">⚠️</span>
            <div>
                <p style="font-weight:700;color:#ef4444;font-size:13.5px">Gagal mengambil data dari Service A</p>
                <p style="font-size:12.5px;color:var(--text-muted);margin-top:2px">{{ $errorMessage }}</p>
            </div>
        </div>
        @endif

        @if (session('status'))
        <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);border-radius:10px;padding:12px 16px;color:#10b981;font-size:12.5px;font-weight:600">
            {{ session('status') }}
        </div>
        @endif

        @if (session('error'))
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:12px 16px;color:#ef4444;font-size:12.5px;font-weight:600">
            {{ session('error') }}
        </div>
        @endif

        {{-- STAT CARDS --}}
        <div class="iq-grid-5">
            <div class="card" style="border-top:3px solid var(--accent)">
                <div class="iq-stat">
                    <div class="iq-stat-lbl">Total Order Aktif</div>
                    <div class="iq-stat-val" style="color:var(--accent)">{{ $summary['total_orders'] }}</div>
                </div>
            </div>
            <div class="card" style="border-top:3px solid #f59e0b">
                <div class="iq-stat">
                    <div class="iq-stat-lbl">Waiting Count</div>
                    <div class="iq-stat-val" style="color:#f59e0b">{{ $summary['waiting_count'] }}</div>
                </div>
            </div>
            <div class="card" style="border-top:3px solid #ef4444">
                <div class="iq-stat">
                    <div class="iq-stat-lbl">Priority High</div>
                    <div class="iq-stat-val" style="color:#ef4444">{{ $summary['priority_counts']['high'] }}</div>
                </div>
            </div>
            <div class="card" style="border-top:3px solid #f97316">
                <div class="iq-stat">
                    <div class="iq-stat-lbl">Priority Medium</div>
                    <div class="iq-stat-val" style="color:#f97316">{{ $summary['priority_counts']['medium'] }}</div>
                </div>
            </div>
            <div class="card" style="border-top:3px solid #10b981">
                <div class="iq-stat">
                    <div class="iq-stat-lbl">Priority Low</div>
                    <div class="iq-stat-val" style="color:#10b981">{{ $summary['priority_counts']['low'] }}</div>
                </div>
            </div>
        </div>

        {{-- MAIN GRID --}}
        <div class="iq-grid-32">

            {{-- ORDERS TABLE --}}
            <div class="card">
                <div class="card-hdr">
                    <div>
                        <div class="card-title">Analisis Queue Orders</div>
                        <div class="card-sub">Diprioritaskan oleh AI · Real-time</div>
                    </div>
                    @php $ks = strtolower($summary['kitchen_status']); @endphp
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:12px;color:var(--text-dim)">Dapur:</span>
                        <span class="badge {{ in_array($ks,['busy','full']) ? 'badge-busy' : ($ks==='slow' ? 'badge-slow' : 'badge-normal') }}">
                            {{ strtoupper($summary['kitchen_status']) }}
                        </span>
                    </div>
                </div>
                <div style="overflow-x:auto">
                    <table class="iq-table">
                        <thead><tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th style="text-align:center">Items</th>
                            <th style="text-align:center">Priority</th>
                            <th>Keterangan AI</th>
                            <th style="text-align:center">Aksi</th>
                        </tr></thead>
                        <tbody>
                            @forelse($orders as $order)
                            @php $p = strtolower($order['priority']); @endphp
                            <tr>
                                <td>
                                    <div style="font-weight:700">{{ $order['order_code'] }}</div>
                                    <div style="font-size:11px;color:var(--text-dim);margin-top:2px">{{ $order['status'] }} · {{ $order['external_status'] }}</div>
                                </td>
                                <td style="font-weight:500">{{ $order['customer_name'] }}</td>
                                <td style="text-align:center">
                                    <span class="badge badge-default">{{ $order['item_count'] }}</span>
                                </td>
                                <td style="text-align:center">
                                    <span class="badge badge-{{ $p === 'high' ? 'high' : ($p === 'medium' ? 'medium' : ($p === 'low' ? 'low' : 'default')) }}">
                                        {{ strtoupper($p) }}
                                    </span>
                                </td>
                                <td style="font-size:12px;color:var(--text-muted);max-width:200px">{{ $order['reason'] }}</td>
                                <td>
                                    <div style="display:flex;gap:6px;justify-content:center">
                                        @if(!in_array(strtolower($order['external_status']), ['processing','done','cancelled']))
                                        <form method="POST" action="{{ route('intelligence.orders.update-status', $order['id']) }}">
                                            @csrf
                                            <input type="hidden" name="target_status" value="processing">
                                            <button type="submit" class="btn-status">Processing</button>
                                        </form>
                                        @endif

                                        @if(strtolower($order['external_status']) !== 'done' && strtolower($order['external_status']) !== 'cancelled')
                                        <form method="POST" action="{{ route('intelligence.orders.update-status', $order['id']) }}">
                                            @csrf
                                            <input type="hidden" name="target_status" value="done">
                                            <button type="submit" class="btn-status btn-status-done">Done</button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-dim)">
                                <div style="font-size:28px;margin-bottom:8px">📭</div> Tidak ada order aktif.
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div style="display:flex;flex-direction:column;gap:16px">

                {{-- TREND INSIGHT --}}
                <div class="card" style="flex:1">
                    <div class="card-hdr">
                        <div><div class="card-title">Trend Insight</div><div class="card-sub">Tren dari AI engine</div></div>
                    </div>
                    <div style="padding:16px">
                        @if(!empty($trends))
                            @foreach($trends as $index => $trend)
                                <div style="border:1px solid var(--border);border-radius:10px;padding:10px;{{ $index > 0 ? 'margin-top:10px' : '' }}">
                                    @if(!empty($trend['image_url']))
                                    <img src="{{ $trend['image_url'] }}" alt="Trend" style="width:100%;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--border);margin-bottom:8px">
                                    @endif
                                    <div style="font-size:13px;font-weight:700;margin-bottom:4px">{{ $trend['title'] }}</div>
                                    <p style="font-size:12px;color:var(--text-muted);line-height:1.6;margin-bottom:8px">{{ $trend['caption'] }}</p>
                                    <div style="display:flex;flex-direction:column;gap:3px">
                                        @foreach(['Score'=>$trend['score'],'Source'=>$trend['source_timestamp'],'Expires'=>$trend['expires_at']] as $k=>$v)
                                        <div style="display:flex;justify-content:space-between;font-size:11.5px">
                                            <span style="color:var(--text-dim)">{{$k}}</span>
                                            <span style="font-weight:600;color:var(--accent)">{{$v}}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div style="text-align:center;padding:28px 10px;color:var(--text-dim)">
                                <div style="font-size:26px;margin-bottom:8px">📈</div>
                                <p style="font-size:12px;line-height:1.5">Belum ada insight tren yang memenuhi threshold.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- AI ENGINE STATUS --}}
                <div class="engine-card">
                    <h3 style="font-size:13px;font-weight:700;margin-bottom:14px;opacity:.9">AI Engine Status</h3>
                    @foreach([['Rule Engine','Aktif','#fff',92],['AI Reasoning','Aktif','#a5f3f5',78],['Queue Processing','Real-time','#6ee7b7',96]] as [$n,$s,$c,$p])
                    <div style="margin-bottom:10px">
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;opacity:.85">
                            <span>{{$n}}</span><span>{{$s}}</span>
                        </div>
                        <div class="engine-bar-track"><div class="engine-bar-fill" style="width:{{$p}}%;background:{{$c}}"></div></div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
