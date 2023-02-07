<html lang="en">
    <head>
        <link href="<?php use Adshares\Common\Domain\ValueObject\SecureUrl;echo SecureUrl::change(
            asset('css/why.css')
        )?>" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">
        <title>{{ $supplyName }}</title>
    </head>

    <body>
        <div id="container">
            @if(in_array($bannerType, ['image', 'html', 'video']))
            <section id="banner-preview">
                <div id="preview-wrapper"></div>
            </section>
            <script>
                const bannerType = '{{$bannerType}}'
                const size = '{{ $bannerSize }}'.split('x');
                const previewWrapper = document.getElementById('preview-wrapper');
                const maxWidth = 700;
                const maxHeight = 100;
                const computeScale = (width, height) => {
                  if (width <= maxWidth && height <= maxHeight) {
                    return 1;
                  }
                  const containerAspect = maxWidth / maxHeight;
                  const aspect = width / height;
                  if (containerAspect < aspect) {
                    return maxWidth / width;
                  }
                  return maxHeight / height;
                }
                const scale = computeScale(parseInt(size[0]), parseInt(size[1])).toFixed(2)
                previewWrapper.style.width = size[0] * scale
                previewWrapper.style.height = size[1] * scale

                if (bannerType === 'image') {
                    previewWrapper.innerHTML = `<img src="{{ $url }}" style="max-width: ${size[0] * scale}; max-height: ${size[1] * scale};"/>`
                }

                if (bannerType === 'html') {
                    previewWrapper.innerHTML = `<iframe id='iframe' src="{{ $url }}" sandbox="allow-scripts"></iframe>`
                    const iframe = document.getElementById('iframe');
                    iframe.style.scale = scale
                    iframe.style.width = size[0];
                    iframe.style.height = size[1];
                    iframe.style.transformOrigin = 'top left'

                }

                if (bannerType === 'video') {
                    previewWrapper.innerHTML = `<video style="max-width: ${size[0] * scale}; max-height: ${size[1] * scale};" autoplay loop muted playsinline src="{{ $url }}"></video>`
                }
            </script>
            @endif
            <section id="supply-info">
                <h3>This ad has been generated by {{ $supplyName }} (<a href="{{ $supplyPanelUrl }}">{{ $supplyPanelUrl }}</a>)</h3>
                <ul>
                    <li>Terms: <a href="{{ $supplyTermsUrl }}">{{ $supplyTermsUrl }}</a></li>
                    <li>Policy: <a href="{{ $supplyPrivacyUrl }}">{{ $supplyPrivacyUrl }}</a></li>
                </ul>
                <div id="ad-report">
                    Report inappropriate ad by clicking the link <a href="{{ $supplyBannerReportUrl }}">{{ $supplyBannerReportUrl }}</a>
                    <br />
                    If you own this site, use direct link <a href="{{ $supplyBannerRejectUrl }}">{{ $supplyBannerRejectUrl }}</a>
                </div>
            </section>

            @if ($demand)
            <section id="demand-info">
                <h3>This ad is provided by {{ $demandName  }} (<a href="{{ $demandPanelUrl }}">{{ $demandPanelUrl }})</a></h3>
                <ul>
                    <li>Terms: <a href="{{ $demandTermsUrl }}">{{ $demandTermsUrl }}</a></li>
                    <li>Policy: <a href="{{ $demandPrivacyUrl }}">{{ $demandPrivacyUrl }}</a></li>
                </ul>
            </section>
            @endif

            <section id="adshares">
                <h3>Ecosystem is powered by Adshares</h3>
                <ul>
                    <li>Website: <a href="https://adshares.net">https://adshares.net</a></li>
                    <li>Contact: <a href="mailto:office@adshares.net">office@adshares.net</a></li>
                </ul>
            </section>
        </div>
    </body>
</html>
