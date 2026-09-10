{{--
    Shared block renderer for the admin guide. Used by both docs.guide-web and
    docs.guide-pdf so the two renderings can never diverge.

    $blocks — array of block descriptors from App\Support\AdminGuide.
    Text is authored in AdminGuide by developers (never user input) and is
    printed unescaped so inline <b>/<i>/<code> markup renders.
--}}
@foreach($blocks as $b)
    @switch($b['type'])

        @case('p')
            <p class="g-p">{!! $b['text'] !!}</p>
            @break

        @case('sub')
            <div class="g-sub">{!! $b['text'] !!}</div>
            @break

        @case('steps')
            <table class="g-steps">
                @foreach($b['items'] as $i => $item)
                <tr>
                    <td class="g-step-n">{{ $i + 1 }}</td>
                    <td class="g-step-t">{!! $item !!}</td>
                </tr>
                @endforeach
            </table>
            @break

        @case('list')
            <table class="g-list">
                @foreach($b['items'] as $item)
                <tr>
                    <td class="g-bullet">&bull;</td>
                    <td class="g-list-t">{!! $item !!}</td>
                </tr>
                @endforeach
            </table>
            @break

        @case('table')
            <table class="g-table">
                <tr>
                    @foreach($b['head'] as $ci => $h)
                    <th @if(!empty($b['widths'][$ci])) style="width:{{ $b['widths'][$ci] }};" @endif>{!! $h !!}</th>
                    @endforeach
                </tr>
                @foreach($b['rows'] as $row)
                <tr>
                    @foreach($row as $cell)
                    <td>{!! $cell !!}</td>
                    @endforeach
                </tr>
                @endforeach
            </table>
            @break

        @case('note')
            <table class="g-call g-note">
                <tr><td>
                    <span class="g-call-t">{!! $b['title'] !!}</span>
                    <span class="g-call-b">{!! $b['text'] !!}</span>
                </td></tr>
            </table>
            @break

        @case('warn')
            <table class="g-call g-warn">
                <tr><td>
                    <span class="g-call-t">{!! $b['title'] !!}</span>
                    <span class="g-call-b">{!! $b['text'] !!}</span>
                </td></tr>
            </table>
            @break

        @case('links')
            <table class="g-links">
                <tr>
                    <td class="g-links-lbl">In the portal</td>
                    <td class="g-links-t">
                        @foreach($b['items'] as $li => $link)
                        {!! $li > 0 ? '<span class="g-sep">&nbsp;·&nbsp;</span>' : '' !!}<a class="g-ext" href="{{ route($link['route']) }}">{{ $link['label'] }} &rarr;</a>
                        @endforeach
                    </td>
                </tr>
            </table>
            @break

    @endswitch
@endforeach
