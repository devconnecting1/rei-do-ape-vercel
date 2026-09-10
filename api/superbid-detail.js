const { makeSlug, setCorsHeaders, handleOptions } = require("./utils");

module.exports = async (req, res) => {
	setCorsHeaders(res);
	if (handleOptions(req, res)) return;

	const id = req.query.id;
	if (!id) return res.status(400).json({ error: "Missing id" });

	const url = `https://exchange.superbid.net/oferta/-${id}`;

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
		const offerDetails = nextData?.props?.pageProps?.offerDetails;
		const offer = offerDetails?.offers?.[0];
		if (!offer) {
			return res.status(404).json({ error: "Offer not found" });
		}

		const photos = (offer.product?.galleryJson || []).map((g) => g.link);
		const thumb = offer.product?.thumbnailUrl || photos[0] || "";
		const template = offer.product?.template;
		const props = {};
		const templateGroups = [];
		if (template?.groups) {
			for (const group of template.groups) {
				const groupData = {
					id: group.id || "",
					title: group.title || "",
					properties: [],
				};
				for (const p of group.properties || []) {
					if (p.value) props[p.id || p.title] = p.value;
					groupData.properties.push({
						id: p.id || "",
						title: p.title || p.id || "",
						value: p.value || "",
					});
				}
				templateGroups.push(groupData);
			}
		}

		const prodLoc = offer.product?.location || {};
		const auctionAddr = offer.auction?.address || {};
		const offerDet = offer.offerDetail || {};

		const result = {
			id: "sb-" + offer.id,
			offerId: offer.id,
			lotNumber: offer.lotNumber,
			title: offer.product?.shortDesc || "",
			price: offer.price,
			priceFormatted: offer.priceFormatted || "",
			initialBid: offerDet.initialBidValue || null,
			initialBidFormatted: offerDet.initialBidValueFormatted || "",
			directSaleValue: offerDet.directSaleValue || null,
			directSaleValueFormatted: offerDet.directSaleValueFormatted || "",
			referenceValue: offerDet.referenceValue || null,
			referenceValueFormatted: offerDet.referenceValueFormatted || "",
			cutValue: offerDet.cutValue || null,
			cutValueFormatted: offerDet.cutValueFormatted || "",
			reservedPrice: offerDet.reservedPrice || null,
			reservedFormatted: offerDet.reservedPriceFormatted || "",
			currentMinBid: offerDet.currentMinBid || null,
			currentMinBidFormatted: offerDet.currentMinBidFormatted || "",
			currentMaxBid: offerDet.currentMaxBid || null,
			currentMaxBidFormatted: offerDet.currentMaxBidFormatted || "",
			thumbnail: thumb,
			photos: photos,
			photoCount: offer.product?.photoCount || 0,
			videoCount: offer.product?.videoUrlCount || 0,
			description:
				offer.product?.detailedDescription ||
				offer.offerDescription?.offerDescription ||
				"",
			properties: props,
			templateGroups: templateGroups,
			subcategory:
				offer.product?.subCategory?.translation?.map?.pt_BR ||
				offer.product?.subCategory?.description ||
				"",
			subCategoryId: offer.product?.subCategory?.id || null,
			category:
				offer.product?.subCategory?.category?.translation?.map?.pt_BR ||
				offer.product?.subCategory?.category?.description ||
				"",
			categoryId: offer.product?.subCategory?.category?.id || null,
			productType:
				offer.product?.productType?.translation?.map?.pt_BR ||
				offer.product?.productType?.description ||
				"",
			productTypeId: offer.product?.productType?.id || null,
			attachments: (offer.product?.attachments || []).map((a) => ({
				name: a.originalFileName || a.fileName,
				url: a.link,
				type: a.contentType,
			})),
			location: {
				city: prodLoc.city || auctionAddr.city || "",
				state: prodLoc.state || auctionAddr.stateCode || "",
				street: auctionAddr.street || "",
				number: auctionAddr.number || "",
				district: auctionAddr.district || "",
				complement: auctionAddr.complement || "",
				country: prodLoc.country || auctionAddr.countryName || "Brasil",
				region: auctionAddr.regionName || "",
				geo: prodLoc.locationGeo || null,
			},
			auction: {
				modality: offer.auction?.modalityDesc || "",
				startDate: offer.auction?.beginDate || "",
				endDate: offer.endDate || "",
				maxEndDate: offer.auction?.maxEnddateOffer || "",
				auctioneer: offer.auction?.auctioneer || "",
				registry: offer.auction?.registry || "",
				praca: offer.auction?.judicialPracaDescription || "",
				subMarketplaces: (offer.auction?.subMarketplaces || []).map(
					(s) => s.subMarketplaceDesc || "",
				),
				stages: (offer.eventPipeline?.stages || []).map((s) => ({
					eventId: s.eventId,
					description: s.eventDesc || "",
					startDate: s.beginDate || "",
					endDate: s.endDate || "",
					initialBid: s.initialBidValue || 0,
					active: s.isActive || false,
				})),
			},
			seller: {
				name: offer.seller?.name || "",
				city: offer.seller?.city || "",
				phones: (offer.seller?.phone || []).map(
					(p) => `+${p.ddi} (${p.ddd}) ${p.number}`,
				),
				company: offer.seller?.company?.[0]?.fantasyName || "",
			},
			stores: (offer.stores || []).map((s) => ({
				name: s.name,
				logo: s.logoUri || "",
			})),
			store: {
				name: offer.store?.name || "",
				logo: offer.store?.logoUri || "",
			},
			manager: offer.manager?.name || "",
			commercialCondition: offer.commercialCondition || null,
			groupOffer: offer.groupOffer || null,
			status: {
				sold: offer.offerStatus?.sold || false,
				reserved: offer.offerStatus?.reserved || false,
				available: offer.offerStatus?.available || false,
				closed: offer.offerStatus?.closed || false,
				giveYourBid: offer.offerStatus?.giveYourBid || false,
			},
			bids: {
				totalBids: offer.totalBids || 0,
				totalBidders: offer.totalBidders || 0,
				increment: offer.currentBidIncrement?.currentBidIncrement || 0,
				incrementFormatted:
					offer.currentBidIncrement?.currentBidIncrementFormatted || "",
			},
			visits: offer.visits || 0,
			url: `https://exchange.superbid.net/oferta/${makeSlug(offer.product?.shortDesc || "oferta")}-${offer.id}`,
		};

		res.setHeader("Cache-Control", "s-maxage=300, stale-while-revalidate");
		return res.status(200).json(result);
	} catch (err) {
		return res.status(500).json({ error: err.message });
	}
};
