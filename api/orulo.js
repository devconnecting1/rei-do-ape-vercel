const { setCorsHeaders, handleOptions } = require("./utils");

module.exports = async (req, res) => {
	setCorsHeaders(res);
	if (handleOptions(req, res)) return;

	const {
		page = 1,
		results_per_page = 10,
		state,
		city,
		min_price,
		max_price,
		min_area,
		max_area,
		bedrooms,
		q,
	} = req.query;

	const url = new URL("https://www.orulo.com.br/api/portal/v2/buildings");
	url.searchParams.set("page", String(page));
	url.searchParams.set("results_per_page", String(results_per_page));
	if (state) url.searchParams.set("state", state);
	if (city) url.searchParams.set("city", city);
	if (min_price) url.searchParams.set("min_price", min_price);
	if (max_price) url.searchParams.set("max_price", max_price);
	if (min_area) url.searchParams.set("min_private_area", min_area);
	if (max_area) url.searchParams.set("max_private_area", max_area);
	if (bedrooms) url.searchParams.set("bedrooms[]", bedrooms);
	if (q) url.searchParams.set("name", q);

	try {
		const r = await fetch(url.toString(), {
			headers: { "User-Agent": "Mozilla/5.0", Accept: "application/json" },
		});
		if (!r.ok)
			return res
				.status(r.status)
				.json({ error: "Orulo API error: " + r.status });
		const data = await r.json();
		res.setHeader("Cache-Control", "s-maxage=300, stale-while-revalidate");
		return res.status(200).json(data);
	} catch (err) {
		return res.status(500).json({ error: err.message });
	}
};
