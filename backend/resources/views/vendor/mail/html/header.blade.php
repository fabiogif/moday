@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php
    $brandName = config('mail.brand.name', config('app.name', 'DistribTec'));
    $logoPath = config('mail.brand.logo_path', '/brand/iconfundotranparente.png');
    $logoUrl = rtrim(config('app.frontend_url', config('app.url')), '/') . $logoPath;
@endphp
<img src="{{ $logoUrl }}" class="logo" alt="{{ $brandName }}" style="background:#ffffff;border-radius:6px;padding:4px;">
</a>
</td>
</tr>
