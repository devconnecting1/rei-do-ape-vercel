const { setCorsHeaders, handleOptions } = require("./utils");

module.exports = async (req, res) => {
	setCorsHeaders(res);
	if (handleOptions(req, res)) return;

	const { id } = req.query;
	if (!id) return res.status(400).json({ error: "id required" });

	try {
		const r = await fetch(
			`https://www.orulo.com.br/api/portal/buildings/${id}`,
			{
				headers: { "User-Agent": "Mozilla/5.0", Accept: "application/json" },
			},
		);
		if (!r.ok)
			return res
				.status(r.status)
				.json({ error: "Orulo API error: " + r.status });
		const data = await r.json();
		res.setHeader("Cache-Control", "s-maxage=600, stale-while-revalidate");
		return res.status(200).json(data);
	} catch (err) {
		return res.status(500).json({ error: err.message });
	}
};
