const CAIXA_HEADERS = {
	"Content-Type": "application/x-www-form-urlencoded",
	"User-Agent":
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
	Accept: "text/html,application/xhtml+xml",
	Origin: "https://venda-imoveis.caixa.gov.br",
	Referer: "https://venda-imoveis.caixa.gov.br/sistema/busca-imovel.asp",
};

const STATE_MAP = {
	"SAO PAULO": "SP",
	"RIO DE JANEIRO": "RJ",
	"MINAS GERAIS": "MG",
	"ESPIRITO SANTO": "ES",
	PARANA: "PR",
	"SANTA CATARINA": "SC",
	"RIO GRANDE DO SUL": "RS",
	GOIAS: "GO",
	"MATO GROSSO": "MT",
	"MATO GROSSO DO SUL": "MS",
	BAHIA: "BA",
	PERNAMBUCO: "PE",
	CEARA: "CE",
	PARAIBA: "PB",
	"RIO GRANDE DO NORTE": "RN",
	PIAUI: "PI",
	MARANHAO: "MA",
	AMAZONAS: "AM",
	PARA: "PA",
	TOCANTINS: "TO",
	"DISTRITO FEDERAL": "DF",
	ACRE: "AC",
	AMAPA: "AP",
	RONDONIA: "RO",
	RORAIMA: "RR",
};

async function caixaGetSession() {
	const https = require("https");
	return new Promise((resolve, reject) => {
		const req = https.request(
			{
				hostname: "venda-imoveis.caixa.gov.br",
				path: "/sistema/busca-imovel.asp",
				method: "GET",
				headers: {
					"User-Agent": CAIXA_HEADERS["User-Agent"],
					Accept: "text/html,application/xhtml+xml",
				},
				timeout: 15000,
			},
			(res) => {
				let data = "";
				res.on("data", (c) => (data += c));
				res.on("end", () => {
					const cookies = (res.headers["set-cookie"] || [])
						.map((c) => c.split(";")[0])
						.join("; ");
					resolve({ status: res.statusCode, body: data, cookies });
				});
			},
		);
		req.on("error", reject);
		req.on("timeout", () => {
			req.destroy();
			reject(new Error("session timeout"));
		});
		req.end();
	});
}

async function caixaPost(url, body, cookies = "") {
	const https = require("https");
	return new Promise((resolve, reject) => {
		const parsed = new URL(url);
		const headers = { ...CAIXA_HEADERS };
		if (cookies) headers["Cookie"] = cookies;
		const req = https.request(
			{
				hostname: parsed.hostname,
				path: parsed.pathname,
				method: "POST",
				headers,
				timeout: 15000,
			},
			(res) => {
				let data = "";
				res.on("data", (c) => (data += c));
				res.on("end", () => resolve({ status: res.statusCode, body: data }));
			},
		);
		req.on("error", reject);
		req.on("timeout", () => {
			req.destroy();
			reject(new Error("request timeout"));
		});
		if (body) req.write(body);
		req.end();
	});
}

let _cachedIds = {};
let _cacheTime = {};
const IDS_CACHE_TTL = 5 * 60 * 1000;

async function searchCaixaIds(state) {
	const now = Date.now();
	if (
		_cachedIds[state] &&
		_cacheTime[state] &&
		now - _cacheTime[state] < IDS_CACHE_TTL
	) {
		return _cachedIds[state];
	}

	const searchBody = new URLSearchParams({
		hdn_estado: state,
		hdn_cidade: "",
		hdn_bairro: "",
		hdn_area_util: "Selecione",
		hdn_faixa_vlr: "Selecione",
		hdn_quartos: "Selecione",
		hdn_tp_imovel: "Selecione",
		hdn_vg_garagem: "Selecione",
		strAceitaFGTS: "",
		strValorSimulador: "",
		strAceitaFinanciamento: "",
		hdn_tp_venda: "",
	}).toString();

	const session = await caixaGetSession();
	const r = await caixaPost(
		"https://venda-imoveis.caixa.gov.br/sistema/carregaPesquisaImoveis.asp",
		searchBody,
		session.cookies,
	);

	if (r.status !== 200) return null;

	const html = r.body;
	const totalPagMatch = /hdnQtdPag'\)\.val\((\d+)\)/.exec(html);
	const totalRegMatch = /hdnQtdRegistros'\)\.val\((\d+)\)/.exec(html);
	const totalPages = totalPagMatch ? parseInt(totalPagMatch[1]) : 1;
	const totalRecords = totalRegMatch ? parseInt(totalRegMatch[1]) : 0;

	const allIds = [];
	const idRegex = /hdnImov(\d+)[^>]*value=([0-9_]+)>/g;
	let match;
	while ((match = idRegex.exec(html)) !== null) {
		const ids = match[2].split("_").filter(Boolean);
		allIds.push(...ids);
	}

	const result = { allIds, totalPages, totalRecords };
	_cachedIds[state] = result;
	_cacheTime[state] = now;
	return result;
}

function clean(s) {
	return s
		? s
				.replace(/<[^>]+>/g, " ")
				.replace(/&nbsp;/g, " ")
				.replace(/\s+/g, " ")
				.trim()
		: "";
}

function parsePrice(str) {
	return Number(String(str).replace(/\./g, "").replace(",", ".")) || 0;
}

function extractCityState(address) {
	const cityStateMatch = /,\s*([^,]+?)\s*-\s*([A-Z\s]+)\s*$/.exec(address);
	const city = cityStateMatch ? cityStateMatch[1].trim() : "";
	const stateFull = cityStateMatch ? cityStateMatch[2].trim() : "";
	const stateCode = STATE_MAP[stateFull.toUpperCase()] || stateFull;
	return { city, stateCode };
}

function extractImages(html, baseUrl = "https://venda-imoveis.caixa.gov.br") {
	const imgRegex = /src=["']([^"']*\.(jpg|jpeg|png|webp))[^"']*/gi;
	const images = [];
	let im;
	while ((im = imgRegex.exec(html)) !== null) {
		let url = im[1];
		if (url.startsWith("/")) url = baseUrl + url;
		if (
			!url.includes("logo") &&
			!url.includes("icon") &&
			!url.includes("banner") &&
			!url.includes("btn") &&
			!images.includes(url)
		) {
			images.push(url);
		}
	}
	return images;
}

function extractDocuments(html, baseUrl = "https://venda-imoveis.caixa.gov.br") {
	const documents = [];

	const docRegex =
		/onclick=javascript:ExibeDoc\('([^']+\.pdf)'\)[^>]*>([^<]+)/gi;
	let dm;
	while ((dm = docRegex.exec(html)) !== null) {
		documents.push({
			url: baseUrl + dm[1],
			name: clean(dm[2]),
		});
	}

	const pdfRegex2 = /href="([^"]*\.pdf[^"]*)"/gi;
	while ((dm = pdfRegex2.exec(html)) !== null) {
		const url = dm[1].startsWith("http") ? dm[1] : baseUrl + dm[1];
		if (!documents.find((d) => d.url === url)) {
			documents.push({
				url,
				name: url
					.split("/")
					.pop()
					.replace(/\.pdf$/i, "")
					.replace(/_/g, " "),
			});
		}
	}

	return documents;
}

function parseCaixaDetail(html, id) {
	const titleMatch = /<h1[^>]*>([\s\S]*?)<\/h1>/i.exec(html);
	const title = titleMatch ? clean(titleMatch[1]) : "";

	const valuationMatch =
		/valor de avalia[çc][ãa]o[\s:]*R\$\s*([\d.,]+)/i.exec(html);

	const price1Match =
		/valor m[ií]nimo de venda\s*1[ºo]\s*Leil[aã]o[\s:]*R\$\s*([\d.,]+)/i.exec(
			html,
		);
	const price2Match =
		/valor m[ií]nimo de venda\s*2[ºo]\s*Leil[aã]o[\s:]*R\$\s*([\d.,]+)/i.exec(
			html,
		);
	const priceGeneric = /valor m[ií]nimo de venda[\s\S]*?R\$\s*([\d.,]+)/i.exec(
		html,
	);
	const priceMatch = price1Match || priceGeneric;

	const addressMatch =
		/<strong>Endere[çc]o:<\/strong>([\s\S]*?)<\/p>/i.exec(html) ||
		/endere[çc]o[\s\S]*?<\/strong>([\s\S]*?)<\/p>/i.exec(html);
	const address = addressMatch ? clean(addressMatch[1]) : "";

	const descMatch =
		/<strong>Descri[çc][ãa]o:<\/strong>([\s\S]*?)<\/p>/i.exec(html) ||
		/descri[çc][ãa]o[\s\S]*?<\/b>([\s\S]*?)(?:<br|<\/div|<div)/i.exec(html);
	const description = descMatch ? clean(descMatch[1]) : "";

	const descSpecs = /(\d+)\s*Quartos.*?(\d+)\s*Vaga/i.exec(description || html);
	const bedroomsMatch =
		descSpecs || /quartos[\s:]*<strong>(\d+)/i.exec(html);
	const garageMatch =
		descSpecs || /Garagem:\s*<strong>(\d+)/i.exec(html);

	const areaPrivativa =
		/[áa]rea\s+privativa\s*=\s*<strong>([\d.,]+)\s*m/i.exec(html);
	const areaTotal = /[áa]rea\s+total\s*=\s*<strong>([\d.,]+)\s*m/i.exec(html);
	const areaMatch = areaPrivativa || areaTotal;

	const typeMatch =
		/Tipo de im[oó]vel[\s\S]*?<strong>([^<]+)/i.exec(html) ||
		/tipo do im[oó]vel[\s\S]*?<strong>([^<]+)/i.exec(html) ||
		/<b>(Leil[^<]+)<\/b>/i.exec(html);

	const situationMatch = /Situa[çc][ãa]o[\s\S]*?<strong>([^<]+)/i.exec(html);
	const matriculaMatch = /Matr[ií]cula[\s\S]*?<strong>([^<]+)/i.exec(html);
	const comarcaMatch = /Comarca[\s\S]*?<strong>([^<]+)/i.exec(html);
	const inscMatch =
		/Inscri[çc][ãa]o imobili[áa]ria[\s\S]*?<strong>([^<]+)/i.exec(html);
	const itemMatch = /N[uú]mero do item:\s*(\d+)/i.exec(html);
	const leiloeiroMatch = /Leiloeiro[\s\S]*?:\s*([^<\n]+)/i.exec(html);

	const editalMatch =
		/Edital:\s*(?:&nbsp;)*([\d\/]+ - [^<\n]+)/i.exec(html) ||
		/Edital[^<]*?:\s*(?:&nbsp;)*([^<\n]+)/i.exec(html);

	const dates1Match =
		/Data do 1[ºo]\s*Leil[aã]o\s*-\s*([\d\/]+ - [\dh]+)/i.exec(html);
	const dates2Match =
		/Data do 2[ºo]\s*Leil[aã]o\s*-\s*([\d\/]+ - [\dh]+)/i.exec(html);

	const auctionDate1 =
		/Data do 1[ºo] Leil[ãa]o\s*-\s*(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d+h\d+)/i.exec(
			html,
		);
	const auctionDate2 =
		/Data do 2[ºo] Leil[ãa]o\s*-\s*(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d+h\d+)/i.exec(
			html,
		);

	const paymentMatch =
		/Formas de pagamento aceitas[\s\S]*?(?:<\/b>|<\/strong>)([\s\S]*?)(?:Regras|<\/div)/i.exec(
			html,
		) || /FORMAS DE PAGAMENTO([\s\S]*?)(?:<br\s*\/>){2}/i.exec(html);

	const fgtsMatch = /Permite utiliza[çc][ãa]o de FGTS/i.test(html);
	const isJudicial = /judicial/i.test(html);

	const images = extractImages(html);
	const documents = extractDocuments(html);
	const { city, stateCode } = extractCityState(address);

	return {
		id: "cx-" + id,
		source: "caixa",
		sourceUrl:
			"https://venda-imoveis.caixa.gov.br/sistema/detalhe-imovel.asp?hdnimovel=" +
			id,
		title: title || "Imóvel Caixa",
		category: "imoveis",
		subcategory: typeMatch ? clean(typeMatch[1]) : "",
		situation: situationMatch ? clean(situationMatch[1]) : "",
		brand: "Caixa Econômica Federal",
		state: stateCode,
		city,
		neighborhood: "",
		location: address,
		address,
		price: priceMatch ? parsePrice(priceMatch[1]) : 0,
		priceFormatted: priceMatch ? "R$ " + priceMatch[1] : "",
		price2Leilao: price2Match ? parsePrice(price2Match[1]) : 0,
		price2LeilaoFormatted: price2Match ? "R$ " + price2Match[1] : "",
		initialBid: valuationMatch ? parsePrice(valuationMatch[1]) : 0,
		initialBidFormatted: valuationMatch ? "R$ " + valuationMatch[1] : "",
		discount: 0,
		status: "Aberto",
		isJudicial,
		auctionName: "Caixa Econômica Federal",
		auctionDate: dates1Match
			? clean(dates1Match[1])
			: auctionDate1
				? auctionDate1[1] + " " + (auctionDate2 ? auctionDate2[1] : "")
				: "",
		edital: editalMatch ? clean(editalMatch[1]) : "",
		itemNumber: itemMatch ? itemMatch[1] : "",
		leiloeiro: leiloeiroMatch ? clean(leiloeiroMatch[1]) : "",
		comarca: comarcaMatch ? clean(comarcaMatch[1]) : "",
		inscricaoImobiliaria: inscMatch ? clean(inscMatch[1]) : "",
		matricula: matriculaMatch ? clean(matriculaMatch[1]) : "",
		fgts: fgtsMatch,
		paymentInfo: paymentMatch ? clean(paymentMatch[1]) : "",
		bedrooms: bedroomsMatch ? bedroomsMatch[1] || bedroomsMatch[2] : "",
		garage: garageMatch ? garageMatch[1] || garageMatch[2] : "",
		usefulArea: areaMatch
			? areaMatch[1].replace(".", "").replace(",", ".")
			: "",
		totalArea: areaTotal
			? areaTotal[1].replace(".", "").replace(",", ".")
			: "",
		occupied: situationMatch
			? clean(situationMatch[1]).toLowerCase().includes("ocupado")
				? "Ocupado"
				: "Desocupado"
			: "",
		description,
		payment: paymentMatch ? clean(paymentMatch[1]) : "",
		tags: [],
		images: images.slice(0, 10),
		imageCount: images.length,
		documents,
	};
}

function makeSlug(str) {
	return str
		.normalize("NFD")
		.replace(/[\u0300-\u036f]/g, "")
		.toLowerCase()
		.replace(/[^a-z0-9\s-]/g, "")
		.replace(/\s+/g, "-")
		.replace(/-+/g, "-")
		.replace(/^-|-$/g, "");
}

function setCorsHeaders(res) {
	res.setHeader("Access-Control-Allow-Origin", "*");
	res.setHeader("Access-Control-Allow-Methods", "GET, OPTIONS");
}

function handleOptions(req, res) {
	if (req.method === "OPTIONS") {
		res.status(200).end();
		return true;
	}
	return false;
}

module.exports = {
	CAIXA_HEADERS,
	STATE_MAP,
	caixaGetSession,
	caixaPost,
	searchCaixaIds,
	clean,
	parsePrice,
	extractCityState,
	extractImages,
	extractDocuments,
	parseCaixaDetail,
	makeSlug,
	setCorsHeaders,
	handleOptions,
};
