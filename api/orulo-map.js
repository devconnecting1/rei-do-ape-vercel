const { setCorsHeaders, handleOptions } = require("./utils");

module.exports = async (req, res) => {
	setCorsHeaders(res);
	if (handleOptions(req, res)) return;

	const { state, city, bedrooms, q, total_pages = 2 } = req.query;
	const pages = Math.min(Number(total_pages) || 2, 20);

	try {
		const allMarkers = [];
		const fetches = [];
		for (let p = 1; p <= pages; p++) {
			const url = new URL("https://www.orulo.com.br/api/portal/v2/buildings");
			url.searchParams.set("page", String(p));
			url.searchParams.set("results_per_page", "50");
			if (state) url.searchParams.set("state", state);
			if (city) url.searchParams.set("city", city);
			if (bedrooms) url.searchParams.set("bedrooms[]", bedrooms);
			if (q) url.searchParams.set("name", q);
			const controller = new AbortController();
			const timer = setTimeout(() => controller.abort(), 12000);
			fetches.push(
				fetch(url.toString(), {
					headers: { "User-Agent": "Mozilla/5.0", Accept: "application/json" },
					signal: controller.signal,
				})
					.then((r) => {
						clearTimeout(timer);
						return r.ok ? r.json() : { buildings: [] };
					})
					.catch(() => {
						clearTimeout(timer);
						return { buildings: [] };
					}),
			);
		}
		const results = await Promise.all(fetches);
		results.forEach((data) => {
			(data.buildings || []).forEach((b) => {
				if (!b.address || !b.address.latitude || !b.address.longitude) return;
				allMarkers.push({
					id: b.id,
					name: b.name,
					price: b.min_price || 0,
					lat: b.address.latitude,
					lng: b.address.longitude,
				});
			});
		});
		res.setHeader("Cache-Control", "s-maxage=600, stale-while-revalidate");
		return res
			.status(200)
			.json({ markers: allMarkers, total: allMarkers.length });
	} catch (err) {
		return res.status(500).json({ error: err.message });
	}
};
