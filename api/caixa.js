const {
	searchCaixaIds,
	caixaGetSession,
	caixaPost,
	parseCaixaDetail,
	setCorsHeaders,
	handleOptions,
} = require("./utils");

module.exports = async (req, res) => {
	setCorsHeaders(res);
	if (handleOptions(req, res)) return;

	const { state = "SP", page = 1, bedrooms = "", type = "" } = req.query;
	const pageNum = Number(page) || 1;
	const perPage = 10;

	try {
		const searchData = await searchCaixaIds(state);
		if (!searchData) {
			return res
				.status(503)
				.json({ error: "Nenhum dado disponível para este estado" });
		}

		const { allIds, totalPages, totalRecords } = searchData;
		const startIdx = (pageNum - 1) * perPage;
		const pageIds = allIds.slice(startIdx, startIdx + perPage);

		if (pageIds.length === 0) {
			return res.status(200).json({
				total: totalRecords,
				totalPages,
				page: pageNum,
				items: [],
				state,
			});
		}

		const detailUrl =
			"https://venda-imoveis.caixa.gov.br/sistema/detalhe-imovel.asp";

		const session = await caixaGetSession();

		const detailResults = await Promise.allSettled(
			pageIds.map((id) =>
				caixaPost(detailUrl, "hdnimovel=" + id, session.cookies).then((r) => {
					if (r && r.status === 200) return { html: r.body, id };
					return null;
				}),
			),
		);

		const items = [];
		for (const result of detailResults) {
			if (result.status === "fulfilled" && result.value) {
				items.push(parseCaixaDetail(result.value.html, result.value.id));
			}
		}

		res.setHeader("Cache-Control", "s-maxage=600, stale-while-revalidate");
		return res.status(200).json({
			total: totalRecords,
			totalPages,
			page: pageNum,
			items,
			state,
		});
	} catch (err) {
		return res.status(500).json({ error: err.message });
	}
};
