#!/usr/bin/env python3
"""One-shot helper to regenerate database/data/states.json and cities.json from IBGE.

Run only when updating the official dataset. The Laravel seeder never calls the network.
"""

from __future__ import annotations

import gzip
import json
import ssl
import urllib.request
from pathlib import Path

CAPITALS = {
    "AC": "Rio Branco",
    "AL": "Maceió",
    "AP": "Macapá",
    "AM": "Manaus",
    "BA": "Salvador",
    "CE": "Fortaleza",
    "DF": "Brasília",
    "ES": "Vitória",
    "GO": "Goiânia",
    "MA": "São Luís",
    "MT": "Cuiabá",
    "MS": "Campo Grande",
    "MG": "Belo Horizonte",
    "PA": "Belém",
    "PB": "João Pessoa",
    "PR": "Curitiba",
    "PE": "Recife",
    "PI": "Teresina",
    "RJ": "Rio de Janeiro",
    "RN": "Natal",
    "RS": "Porto Alegre",
    "RO": "Porto Velho",
    "RR": "Boa Vista",
    "SC": "Florianópolis",
    "SP": "São Paulo",
    "SE": "Aracaju",
    "TO": "Palmas",
}


def fetch(url: str):
    req = urllib.request.Request(
        url,
        headers={
            "Accept": "application/json",
            "Accept-Encoding": "gzip, deflate",
            "User-Agent": "distribtec-ibge-seed/1.0",
        },
    )
    ctx = ssl.create_default_context()
    with urllib.request.urlopen(req, context=ctx, timeout=180) as response:
        raw = response.read()
        if raw[:2] == b"\x1f\x8b":
            raw = gzip.decompress(raw)
        return json.loads(raw.decode("utf-8"))


def main() -> None:
    root = Path(__file__).resolve().parents[1] / "database" / "data"
    root.mkdir(parents=True, exist_ok=True)

    estados = fetch(
        "https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome"
    )
    municipios = fetch(
        "https://servicodados.ibge.gov.br/api/v1/localidades/municipios"
    )

    states = []
    for estado in estados:
        region = estado.get("regiao") or {}
        states.append(
            {
                "ibge_code": str(estado["id"]),
                "uf": estado["sigla"],
                "name": estado["nome"],
                "region": region.get("nome", ""),
            }
        )

    cities = []
    for municipio in municipios:
        micro = municipio.get("microrregiao") or {}
        meso = micro.get("mesorregiao") or {}
        uf_obj = meso.get("UF") or {}
        if not uf_obj.get("sigla"):
            imediata = municipio.get("regiao-imediata") or {}
            intermediaria = imediata.get("regiao-intermediaria") or {}
            uf_obj = intermediaria.get("UF") or {}
        uf = uf_obj.get("sigla")
        if not uf:
            continue
        name = municipio["nome"]
        cities.append(
            {
                "ibge_code": str(municipio["id"]),
                "state_uf": uf,
                "name": name,
                "is_capital": CAPITALS.get(uf) == name,
            }
        )

    states.sort(key=lambda item: item["name"])
    cities.sort(key=lambda item: (item["state_uf"], item["name"]))

    (root / "states.json").write_text(
        json.dumps(states, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    (root / "cities.json").write_text(
        json.dumps(cities, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(f"Wrote {len(states)} states and {len(cities)} cities to {root}")


if __name__ == "__main__":
    main()
