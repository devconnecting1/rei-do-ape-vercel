const {
	caixaPost,
	parseCaixaDetail,
	setCorsHeaders,
	handleOptions,
} = require("./utils");

module.exports = async (req, res) => {
	setCorsHeaders(res);
	if (handleOptions(req, res)) return;

	const { id } = req.query;
	if (!id) return res.status(400).json({ error: "id required" });

	const rawId = id.replace("cx-", "");

	try {
		const url =
			"https://venda-imoveis.caixa.gov.br/sistema/detalhe-imovel.asp";
		const r = await caixaPost(url, "hdnimovel=" + rawId);

		if (!r || r.status !== 200 || r.body.length < 100) {
			return res
				.status(503)
				.json({ error: "Não foi possível acessar o imóvel" });
		}

		const result = parseCaixaDetail(r.body, rawId);

		res.setHeader("Cache-Control", "s-maxage=600, stale-while-revalidate");
		return res.status(200).json(result);
	} catch (err) {
		return res.status(500).json({ error: err.message });
	}
};
