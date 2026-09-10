const { makeSlug, setCorsHeaders, handleOptions } = require("./utils");

const CATEGORIES = {
	imoveis: "imoveis",
	carros: "carros-motos",
	caminhoes: "caminhoes-onibus",
	"maquinas-agricolas": "maquinas-pesadas-agricolas",
	transporte: "movimentacao-transporte",
	industrial: "industrial-maquinas-equipamentos",
	animais: "animais",
	tecnologia: "tecnologia",
	moveis: "moveis-e-decoracao",
	joias: "bolsas-canetas-joias-e-relogios",
	sucatas: "sucatas-materiais-residuos",
};

module.exports = async (req, res) => {
	setCorsHeaders(res);
	if (handleOptions(req, res)) return;

	const category = CATEGORIES[req.query.category] || "imoveis";
	const page = Number(req.query.page) || 1;
	const pageSize = Number(req.query.pageSize) || 10;

	const url = `https://exchange.superbid.net/categorias/${category}?pageNumber=${page}&pageSize=${pageSize}&orderBy=score:desc`;

	try {
		const resp = await fetch(url, {
			headers: {
				Accept: "text/html,application/xhtml+xml",
				"User-Agent":
					"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
			},
		});

		if (!resp.ok) {
			return res.status(502).json({ error: `Superbid API ${resp.status}` });
		}

		const html = await resp.text();
		const match = html.match(/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/);
		if (!match) {
			return res.status(502).json({ error: "Could not parse Superbid page" });
		}

		const nextData = JSON.parse(match[1]);
		const offersList = nextData?.props?.pageProps?.offersList;
		const offers = offersList?.offers || [];
		const total = offersList?.total || 0;

		const items = offers.map((o) => {
			const desc = o.product?.shortDesc || "";
			const thumb =
				o.product?.thumbnailUrl || o.product?.galleryJson?.[0]?.link || "";
			const photos = (o.product?.galleryJson || []).map((g) => g.link);
			const storeName = o.store?.name || "";
			const auctioneer = o.auction?.auctioneer || "";

			return {
				id: "sb-" + o.id,
				offerId: o.id,
				lotNumber: o.lotNumber,
				title: desc,
				price: o.price,
				priceFormatted: o.priceFormatted || "",
				cutValue: o.offerDetail?.cutValue || null,
				cutFormatted: o.offerDetail?.cutFormatted || "",
				referenceValue: o.offerDetail?.referenceValue || null,
				referenceFormatted: o.offerDetail?.referenceFormatted || "",
				thumbnail: thumb,
				photos: photos,
				photoCount: o.product?.photoCount || 0,
				store: storeName,
				auctioneer: auctioneer,
				endDate: o.endDate || "",
				sold: o.offerStatus?.sold || false,
				available: o.offerStatus?.available || false,
				reserved: o.offerStatus?.reserved || false,
				visits: o.visits || 0,
				url: `https://exchange.superbid.net/oferta/${makeSlug(desc)}-${o.id}`,
			};
		});

		res.setHeader("Cache-Control", "s-maxage=300, stale-while-revalidate");
		return res.status(200).json({
			items,
			total,
			page,
			pageSize,
			category,
			categories: Object.keys(CATEGORIES),
		});
	} catch (err) {
		return res.status(500).json({ error: err.message });
	}
};
