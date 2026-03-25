/**
 * AI Governance and Infrastructure Suite — Chart.js Initialisation
 *
 * Reads window.aigisChartData (set by wp_localize_script) and renders
 * all charts declared within it.
 *
 * Depends on: Chart.js 4.x (registered as 'chartjs' by AIGIS_Admin)
 */

/* global aigisChartData, Chart */
( function () {
	'use strict';

	if ( typeof aigisChartData === 'undefined' ) {
		return;
	}

	const BLUE   = '#2271b1';
	const GREEN  = '#00a32a';
	const AMBER  = '#dba617';
	const RED    = '#d63638';
	const GRAY   = '#8c8f94';

	const PALETTE = [
		'#2271b1', '#00a32a', '#dba617', '#d63638',
		'#8c8f94', '#9b59b6', '#1abc9c', '#e67e22',
		'#3498db', '#f39c12', '#2ecc71', '#e74c3c',
	];

	// -----------------------------------------------------------------------
	// Default chart options
	// -----------------------------------------------------------------------
	const defaultOptions = {
		responsive: true,
		maintainAspectRatio: false,
		plugins: {
			legend: {
				labels: { boxWidth: 12, font: { size: 12 } },
			},
			tooltip: {
				mode: 'index',
				intersect: false,
			},
		},
	};

	const lineScales = {
		x: { grid: { color: '#f0f0f1' } },
		y: { beginAtZero: true, grid: { color: '#f0f0f1' } },
	};

	// -----------------------------------------------------------------------
	// Usage trend (line chart)
	// -----------------------------------------------------------------------
	const $usage = document.getElementById( 'aigis-chart-usage-trend' );
	if ( $usage && aigisChartData.usageTrend ) {
		const d = aigisChartData.usageTrend;
		new Chart( $usage, {
			type: 'line',
			data: {
				labels  : d.labels,
				datasets: [
					{
						label          : 'Sessions',
						data           : d.sessions,
						borderColor    : BLUE,
						backgroundColor: BLUE + '22',
						tension        : 0.3,
						fill           : true,
						pointRadius    : 3,
					},
					...( d.tokens ? [ {
						label       : 'Tokens (÷100)',
						data        : d.tokens.map( v => Math.round( v / 100 ) ),
						borderColor : GREEN,
						tension     : 0.3,
						fill        : false,
						pointRadius : 3,
						yAxisID     : 'y',
					} ] : [] ),
				],
			},
			options: { ...defaultOptions, scales: lineScales },
		} );
	}

	// -----------------------------------------------------------------------
	// Model breakdown (doughnut)
	// -----------------------------------------------------------------------
	const $models = document.getElementById( 'aigis-chart-model-breakdown' );
	if ( $models && aigisChartData.modelBreakdown ) {
		const d = aigisChartData.modelBreakdown;
		new Chart( $models, {
			type: 'doughnut',
			data: {
				labels  : d.labels,
				datasets: [ {
					data           : d.tokens,
					backgroundColor: PALETTE.slice( 0, d.labels.length ),
					borderWidth    : 2,
				} ],
			},
			options: {
				...defaultOptions,
				plugins: {
					...defaultOptions.plugins,
					legend: { position: 'right', labels: { boxWidth: 12 } },
				},
			},
		} );
	}

	// -----------------------------------------------------------------------
	// Department cost (horizontal bar)
	// -----------------------------------------------------------------------
	const $deptCost = document.getElementById( 'aigis-chart-dept-cost' );
	if ( $deptCost && aigisChartData.deptCost ) {
		const d = aigisChartData.deptCost;
		new Chart( $deptCost, {
			type: 'bar',
			data: {
				labels  : d.labels,
				datasets: [ {
					label          : 'Cost (USD)',
					data           : d.costs,
					backgroundColor: BLUE + 'cc',
					borderRadius   : 3,
				} ],
			},
			options: {
				...defaultOptions,
				indexAxis: 'y',
				scales: {
					x: { beginAtZero: true, grid: { color: '#f0f0f1' } },
					y: { grid: { display: false } },
				},
			},
		} );
	}

	// -----------------------------------------------------------------------
	// Eval pass-rate trend (line)
	// -----------------------------------------------------------------------
	const $evalTrend = document.getElementById( 'aigis-chart-eval-trend' );
	if ( $evalTrend && aigisChartData.evalTrend ) {
		const d = aigisChartData.evalTrend;
		new Chart( $evalTrend, {
			type: 'line',
			data: {
				labels  : d.labels,
				datasets: [
					{
						label       : 'Pass rate %',
						data        : d.pass_rate,
						borderColor : GREEN,
						tension     : 0.3,
						fill        : false,
						pointRadius : 3,
					},
					{
						label       : 'Fail rate %',
						data        : d.fail_rate,
						borderColor : RED,
						tension     : 0.3,
						fill        : false,
						pointRadius : 3,
					},
				],
			},
			options: {
				...defaultOptions,
				scales: {
					...lineScales,
					y: { min: 0, max: 100, grid: { color: '#f0f0f1' } },
				},
			},
		} );
	}

	// -----------------------------------------------------------------------
	// Cost trend (line with budget overlay)
	// -----------------------------------------------------------------------
	const $costTrend = document.getElementById( 'aigis-chart-cost-trend' );
	if ( $costTrend && aigisChartData.costTrend ) {
		const d = aigisChartData.costTrend;
		const datasets = [
			{
				label       : 'Actual Spend ($)',
				data        : d.actual,
				borderColor : BLUE,
				tension     : 0.3,
				fill        : false,
				pointRadius : 3,
			},
		];
		if ( d.budget ) {
			datasets.push( {
				label       : 'Budget ($)',
				data        : d.budget,
				borderColor : AMBER,
				borderDash  : [ 6, 3 ],
				tension     : 0,
				fill        : false,
				pointRadius : 0,
			} );
		}
		new Chart( $costTrend, {
			type: 'line',
			data: { labels: d.labels, datasets },
			options: { ...defaultOptions, scales: lineScales },
		} );
	}

	// -----------------------------------------------------------------------
	// Budget utilisation (bar)
	// -----------------------------------------------------------------------
	const $budgetBar = document.getElementById( 'aigis-chart-budget-utilisation' );
	if ( $budgetBar && aigisChartData.budgetUtil ) {
		const d = aigisChartData.budgetUtil;
		new Chart( $budgetBar, {
			type: 'bar',
			data: {
				labels  : d.labels,
				datasets: [
					{
						label          : 'Used ($)',
						data           : d.used,
						backgroundColor: d.used.map( ( v, i ) =>
							d.pct[ i ] >= 100 ? RED + 'cc' :
							d.pct[ i ] >= 80  ? AMBER + 'cc' : BLUE + 'cc' ),
						borderRadius: 3,
					},
					{
						label          : 'Remaining ($)',
						data           : d.remaining,
						backgroundColor: GRAY + '55',
						borderRadius   : 3,
					},
				],
			},
			options: {
				...defaultOptions,
				scales: {
					x: { stacked: true, grid: { display: false } },
					y: { stacked: true, beginAtZero: true, grid: { color: '#f0f0f1' } },
				},
			},
		} );
	}

} )();
