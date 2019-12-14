<?php
// letters
const ALPHA_RANGE = "902,913,940,945,7936-7951,8048-8049,8064-8079,8112-8116,8118";
const BETA_RANGE = "914,946";
const GAMMA_RANGE = "915,947";
const DELTA_RANGE = "916,948";
const EPSILON_RANGE = "904,917,941,949,7952-7957,7960-7965,8050-8051,8136";
const ZETA_RANGE = "918,950";
const ETA_RANGE = "905,919,942,951,7968-7983,8052-8053,8080-8095,8130-8132,8134-8135,8138";
const THETA_RANGE = "920,952";
const IOTA_RANGE = "906,912,921,938,943,953,970,7984-7999,8054-8055,8144-8147,8150";
const KAPPA_RANGE = "922,954";
const LAMBDA_RANGE = "923,955";
const MU_RANGE = "924,956";
const NU_RANGE = "925,957";
const XI_RANGE = "926,958";
const OMICRON_RANGE = "908,927,959,972,8000-8005,8008-8013,8056-8057,8184";
const PI_RANGE = "928,960";
const RHO_RANGE = "929,961,8164-8165,8172";
const SIGMA_RANGE = "931,963";
const TAU_RANGE = "932,964";
const UPSILON_RANGE = "910,933,939,944,965,971,973,8016-8023,8025,8027,8029,8031,8058-8059,8160-8163,8166";
const PHI_RANGE = "934,966";
const CHI_RANGE = "935,967";
const PSI_RANGE = "936,968";
const OMEGA_RANGE = "911,937,969,974,8032-8047,8060-8061,8096-8111,8178-8180,8182-8183,8186";
const ALPHABET_RANGES = array(
    'A' => ALPHA_RANGE, 'B' => BETA_RANGE, 'G' => GAMMA_RANGE,
    'D' => DELTA_RANGE, 'E' => EPSILON_RANGE, 'Z' => ZETA_RANGE,
    'H' => ETA_RANGE, 'Q' => THETA_RANGE, 'I' => IOTA_RANGE,
    'K' => KAPPA_RANGE, 'L' => LAMBDA_RANGE, 'M' => MU_RANGE,
    'N' => NU_RANGE, 'C' => XI_RANGE, 'O' => OMICRON_RANGE,
    'P' => PI_RANGE, 'R' => RHO_RANGE, 'S' => SIGMA_RANGE,
    'T' => TAU_RANGE, 'U' => UPSILON_RANGE, 'F' => PHI_RANGE,
    'X' => CHI_RANGE, 'Y' => PSI_RANGE, 'W' => OMEGA_RANGE
);

// character case
const CAPITAL_RANGE = "902,904-906,908,910-911,913-929,931-939,7944-7951,7960-7965,7976-7983,7992-7999,8008-8013,8025,8027,8029,8031,8040-8047,8072-8079,8088-8095,8104-8111,8120-8121,8136-8140,8152-8155,8168-8172,8184";

// diacritics
const ACUTE_RANGE = "902,904-906,908,910-912,940-944,972-974,7940-7941,7948-7949,7956-7957,7964-7965,7972-7973,7980-7981,7988-7989,7996-7997,8004-8005,8012-8013,8020-8021,8029,8036-8037,8044-8045,8049,8051,8053,8055,8057,8059,8061,8068-8069,8076-8077,8084-8085,8092-8093,8098,8100-8101,8108-8109,8116,8123,8132,8137,8139,8147,8155,8163,8171,8180,8185,8187";
const GRAVE_RANGE = "7938-7939,7946-7947,7954-7955,7962-7963,7970-7971,7978-7979,7986-7987,7994-7995,8002-8003,8010-8011,8018-8019,8027,8034-8035,8042-8043,8048,8050,8052,8054,8056,8058,8060,8066-8067,8074-8075,8082-8083,8090-8091,8099,8106-8107,8114,8122,8130,8136,8138,8146,8154,8162,8170,8178,8184,8186";
const CIRCUMFLEX_RANGE = "7942-7943,7950-7951,7974-7975,7982-7983,7990-7991,7998-7999,8022-8023,8031,8038-8039,8046-8047,8070-8071,8078-8079,8086-8087,8094-8095,8102-8103,8110-8111,8118-8119,8134-8135,8150-8151,8166-8167,8182";
const MACRON_RANGE = "8113,8121,8145,8153,8161,8169";
const BREVE_RANGE = "8112,8120,8144,8152,8160,8168";
const DIAERESIS_RANGE = "912,938-939,944,970-971,8146-8147,8151,8162-8163,8167";
const ROUGH_RANGE = "7937,7939,7941,7943,7945,7947,7949,7951,7953,7955,7957,7961,7963,7965,7969,7971,7973,7975,7977,7979,7981,7983,7985,7987,7989,7991,7993,7995,7997,7999,8001,8003,8005,8009,8011,8013,8017,8019,8021,8023,8025,8027,8029,8031,8033,8035,8037,8039,8041,8043,8045,8047,8065,8067,8069,8071,8073,8075,8077,8079,8081,8083,8085,8087,8089,8091,8093,8095,8097,8099,8101,8103,8105,8107,8109,8111,8165,8172";
const SMOOTH_RANGE = "7936,7938,7940,7942,7944,7946,7948,7950,7952,7954,7956,7960,7962,7964,7968,7970,7972,7974,7976,7978,7980,7982,7984,7986,7988,7990,7992,7994,7996,7998,8000,8002,8004,8008,8010,8012,8016,8018,8020,8022,8032,8034,8036,8038,8040,8042,8044,8046,8064,8066,8068,8070,8072,8074,8076,8078,8080,8082,8084,8086,8088,8090,8092,8094,8096,8098,8100,8102,8104,8106,8108,8110,8164";
const IOTASUB_RANGE = "8064-8111,8114-8116,8119,8124,8130-8132,8135,8140,8178-8180,8183,8188";
const DIACRITIC_RANGES = array(
    '/' => ACUTE_RANGE, '\\' => GRAVE_RANGE, '=' => CIRCUMFLEX_RANGE,
    '_' => MACRON_RANGE, '^' => BREVE_RANGE, '+' => DIAERESIS_RANGE,
    '(' => ROUGH_RANGE, ')' => SMOOTH_RANGE, '|' => IOTASUB_RANGE
);

// omitted: J (terminal sigma), ?, :