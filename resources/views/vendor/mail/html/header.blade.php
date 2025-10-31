@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if(trim($slot) === 'tempus')
<img src="{{ asset('images/tempus-logo.png') }}" srcset="{{ asset('images/tempus-logo.svg') }}" class="logo" alt="tempus Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
