@props(['booking'])

<div class="lookup-context">
    <section class="staff-panel lookup-context__panel" aria-labelledby="lookup-context-title">
        <h2 id="lookup-context-title">Admission context</h2>
        <dl>
            <div><dt>Selected show</dt><dd>{{ $booking['time'] }} · {{ $booking['screen'] }}</dd></div>
            <div><dt>Seats / scanned</dt><dd>{{ $booking['seats'] }} / {{ $booking['scanned'] }}</dd></div>
            <div><dt>Entry window</dt><dd>{{ $booking['window'] }}</dd></div>
        </dl>
        <p>Read-only result. No payment, refund or email details are shown.</p>
    </section>

    <aside class="staff-notice staff-notice--warning">
        <p><strong>ⓘ Single-use rules still apply</strong></p>
        <p>Already-used, void and out-of-window tickets cannot be admitted through reference lookup.</p>
    </aside>
</div>
