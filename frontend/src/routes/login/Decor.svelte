<!-- Gradiens háttér -->
<div class="absolute inset-0 bg-linear-to-br from-blue-950 via-blue-800 to-blue-950"></div>
<!-- Hullám dekorációk -->
<svg
	class="absolute inset-0 h-full w-full"
	viewBox="0 0 500 600"
	preserveAspectRatio="xMidYMid slice"
>
	<defs>
		<linearGradient id="wave1" x1="0%" y1="0%" x2="100%" y2="100%">
			<stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.7" />
			<stop offset="100%" style="stop-color:#1d4ed8;stop-opacity:0.3" />
		</linearGradient>
		<linearGradient id="wave2" x1="0%" y1="0%" x2="100%" y2="100%">
			<stop offset="0%" style="stop-color:#60a5fa;stop-opacity:0.5" />
			<stop offset="100%" style="stop-color:#1e40af;stop-opacity:0.2" />
		</linearGradient>
		<linearGradient id="wave3" x1="0%" y1="0%" x2="100%" y2="0%">
			<stop offset="0%" style="stop-color:#93c5fd;stop-opacity:0.6" />
			<stop offset="100%" style="stop-color:#3b82f6;stop-opacity:0.4" />
		</linearGradient>
	</defs>

	<!-- Felső hullám -->
	<path d="M-50 80 Q100 20 200 60 T400 40 T600 80 L600 0 L-50 0 Z" fill="url(#wave1)" />

	<!-- Középső hullámok -->
	<path
		d="M-50 350 Q50 300 150 340 T350 300 T550 360 L550 400 Q400 360 250 390 T-50 380 Z"
		fill="url(#wave2)"
		opacity="0.6"
	/>
	<path
		d="M-50 380 Q80 340 180 370 T380 330 T580 390"
		stroke="url(#wave3)"
		stroke-width="3"
		fill="none"
	/>
	<path
		d="M-50 400 Q100 360 200 390 T400 350 T600 410"
		stroke="url(#wave1)"
		stroke-width="2"
		fill="none"
		opacity="0.7"
	/>

	<!-- Alsó hullám -->
	<path d="M-50 520 Q100 480 200 510 T400 470 T600 530 L600 600 L-50 600 Z" fill="url(#wave2)" />
	<path
		d="M-50 550 Q80 510 180 540 T380 500 T580 560"
		stroke="url(#wave3)"
		stroke-width="2"
		fill="none"
		opacity="0.8"
	/>

	<!-- Extra dekoratív vonalak -->
	<path
		d="M50 200 Q150 180 250 210 T450 170"
		stroke="#3b82f6"
		stroke-width="2"
		fill="none"
		opacity="0.4"
	/>
	<path
		d="M30 220 Q130 200 230 230 T430 190"
		stroke="#60a5fa"
		stroke-width="1"
		fill="none"
		opacity="0.3"
	/>
</svg>
