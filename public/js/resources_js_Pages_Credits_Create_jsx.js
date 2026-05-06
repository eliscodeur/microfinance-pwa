"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["resources_js_Pages_Credits_Create_jsx"],{

/***/ "./resources/js/Layouts/AdminLayout.jsx":
/*!**********************************************!*\
  !*** ./resources/js/Layouts/AdminLayout.jsx ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ AdminLayout)
/* harmony export */ });
/* harmony import */ var _inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @inertiajs/inertia-react */ "./node_modules/@inertiajs/inertia-react/dist/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");


function AdminLayout(_ref) {
  var children = _ref.children;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("nav", {
      className: "navbar navbar-expand-lg navbar-dark bg-dark",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
        className: "container-fluid px-4",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.Link, {
          href: "/admin/dashboard",
          className: "navbar-brand",
          children: "NANA ECO CONSULTING"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
          className: "navbar-toggler",
          type: "button",
          "data-bs-toggle": "collapse",
          "data-bs-target": "#navbarNav",
          "aria-controls": "navbarNav",
          "aria-expanded": "false",
          "aria-label": "Toggle navigation",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
            className: "navbar-toggler-icon"
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
          className: "collapse navbar-collapse",
          id: "navbarNav",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("ul", {
            className: "navbar-nav ms-auto",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
              className: "nav-item",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.Link, {
                href: "/admin/clients",
                className: "nav-link",
                children: "Clients"
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
              className: "nav-item",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.Link, {
                href: "/admin/carnets",
                className: "nav-link",
                children: "Carnets"
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
              className: "nav-item",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.Link, {
                href: "/admin/credits",
                className: "nav-link",
                children: "Cr\xE9dits"
              })
            })]
          })
        })]
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("main", {
      className: "container mt-4",
      children: children
    })]
  });
}

/***/ }),

/***/ "./resources/js/Pages/Credits/Create.jsx":
/*!***********************************************!*\
  !*** ./resources/js/Pages/Credits/Create.jsx ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Create)
/* harmony export */ });
/* harmony import */ var _inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @inertiajs/inertia-react */ "./node_modules/@inertiajs/inertia-react/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Layouts_AdminLayout_jsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../Layouts/AdminLayout.jsx */ "./resources/js/Layouts/AdminLayout.jsx");
/* harmony import */ var _Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../Utils/creditHelpers */ "./resources/js/Utils/creditHelpers.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }





function Create(_ref) {
  var _selectedCarnet$total, _selectedCarnet$requi, _selectedCarnet$avail, _selectedCarnet$guara;
  var clients = _ref.clients;
  var form = (0,_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.useForm)({
    client_id: '',
    carnet_id: '',
    montant_demande: 0,
    type: 'compte',
    mode: 'degressif',
    periodicite: 'mensuelle',
    nombre_echeances: 3,
    taux: 1.5,
    taux_manuelle: '',
    date_debut: new Date().toISOString().slice(0, 10)
  });
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)([]),
    _useState2 = _slicedToArray(_useState, 2),
    carnets = _useState2[0],
    setCarnets = _useState2[1];
  var today = new Date().toISOString().slice(0, 10);
  var isCompteCredit = form.data.type === 'compte';
  var selectedCarnet = carnets.find(function (carnet) {
    return String(carnet.id) === String(form.data.carnet_id);
  });
  var isCompteCarnetSelected = (selectedCarnet === null || selectedCarnet === void 0 ? void 0 : selectedCarnet.type) === 'compte';
  var isTontineCarnetSelected = (selectedCarnet === null || selectedCarnet === void 0 ? void 0 : selectedCarnet.type) === 'tontine';
  var isTypeFixedByCarnet = !!selectedCarnet;
  var pointageWarning = isTontineCarnetSelected && (selectedCarnet === null || selectedCarnet === void 0 ? void 0 : selectedCarnet.total_pointages) < (selectedCarnet === null || selectedCarnet === void 0 ? void 0 : selectedCarnet.required_pointages);
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(function () {
    if (isCompteCarnetSelected && form.data.type !== 'compte') {
      form.setData('type', 'compte');
    }
    if (isTontineCarnetSelected && form.data.type !== 'quinzaine') {
      form.setData('type', 'quinzaine');
    }
  }, [isCompteCarnetSelected, isTontineCarnetSelected, form.data.type]);
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(function () {
    if (!form.data.client_id) {
      setCarnets([]);
      form.setData('carnet_id', '');
      return;
    }
    var controller = new AbortController();
    var url = "/admin/carnets/get-by-client/".concat(form.data.client_id, "?t=").concat(Date.now());
    fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      signal: controller.signal
    }).then(function (response) {
      if (!response.ok) {
        throw new Error("HTTP error! status: ".concat(response.status));
      }
      return response.json();
    }).then(function (data) {
      console.log('Carnets fetched:', data);
      if (Array.isArray(data)) {
        setCarnets(data);
        if (!data.some(function (item) {
          return String(item.id) === String(form.data.carnet_id);
        })) {
          form.setData('carnet_id', '');
        }
      } else {
        console.error('Expected array, got:', data);
        setCarnets([]);
      }
    })["catch"](function (err) {
      console.error('Error fetching carnets:', err);
      setCarnets([]);
    });
    return function () {
      return controller.abort();
    };
  }, [form.data.client_id]);
  var schedule = (0,react__WEBPACK_IMPORTED_MODULE_1__.useMemo)(function () {
    return (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__.buildScheduleFromForm)(form.data);
  }, [form.data]);
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(1),
    _useState4 = _slicedToArray(_useState3, 2),
    currentPage = _useState4[0],
    setCurrentPage = _useState4[1];
  var pageSize = 6;
  var pageCount = Math.max(1, Math.ceil(schedule.length / pageSize));
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(function () {
    setCurrentPage(1);
  }, [schedule.length]);
  var paginatedSchedule = (0,react__WEBPACK_IMPORTED_MODULE_1__.useMemo)(function () {
    var start = (currentPage - 1) * pageSize;
    return schedule.slice(start, start + pageSize);
  }, [schedule, currentPage]);
  var totalInterest = (0,react__WEBPACK_IMPORTED_MODULE_1__.useMemo)(function () {
    return schedule.reduce(function (sum, row) {
      return sum + row.interest;
    }, 0);
  }, [schedule]);
  var totalDue = (0,react__WEBPACK_IMPORTED_MODULE_1__.useMemo)(function () {
    return schedule.reduce(function (sum, row) {
      return sum + row.total;
    }, 0);
  }, [schedule]);
  var meanInstallment = (0,react__WEBPACK_IMPORTED_MODULE_1__.useMemo)(function () {
    return schedule.length ? totalDue / schedule.length : 0;
  }, [schedule, totalDue]);
  var submit = function submit(e) {
    e.preventDefault();
    form.post('/admin/credits', {
      onSuccess: function onSuccess() {
        form.reset('montant_demande', 'type', 'mode', 'periodicite', 'nombre_echeances', 'taux', 'taux_manuelle', 'date_debut');
      }
    });
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_Layouts_AdminLayout_jsx__WEBPACK_IMPORTED_MODULE_2__["default"], {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
        className: "d-flex justify-content-between align-items-center mb-4",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("h1", {
            className: "h3",
            children: "Nouvelle demande de cr\xE9dit"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("p", {
            className: "text-muted mb-0",
            children: "Saisie interactive et pr\xE9visualisation des \xE9ch\xE9ances."
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.Link, {
          href: "/admin/credits",
          className: "btn btn-outline-secondary",
          children: "Retour"
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("form", {
        onSubmit: submit,
        className: "card shadow-sm p-4",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
          className: "row gy-3",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
            className: "col-md-6",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("span", {
              children: ["Nombre de carnets : ", carnets.length]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("label", {
              className: "form-label",
              children: "Client"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("select", {
              className: "form-select",
              value: form.data.client_id,
              onChange: function onChange(e) {
                return form.setData('client_id', e.target.value);
              },
              required: true,
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                value: "",
                children: "S\xE9lectionner un client"
              }), clients.map(function (client) {
                return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("option", {
                  value: client.id,
                  children: [client.nom, " ", client.prenom]
                }, client.id);
              })]
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
            className: "col-md-6",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("label", {
              className: "form-label",
              children: ["Carnet", isCompteCredit && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("span", {
                className: "text-danger ms-2",
                children: "*"
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("select", {
              className: "form-select ".concat(form.errors.carnet_id ? 'is-invalid' : ''),
              value: form.data.carnet_id,
              onChange: function onChange(e) {
                return form.setData('carnet_id', e.target.value);
              },
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                value: "",
                children: isCompteCredit ? 'Sélectionner un carnet obligatoire' : 'Sélectionner un carnet (optionnel)'
              }), carnets.length === 0 && form.data.client_id && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                value: "",
                children: "Aucun carnet actif trouv\xE9"
              }), carnets.map(function (carnet) {
                return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("option", {
                  value: carnet.id,
                  children: [carnet.type === 'tontine' ? 'Tontine' : 'Compte', " ", carnet.numero]
                }, carnet.id);
              })]
            }), form.errors.carnet_id && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
              className: "invalid-feedback",
              children: form.errors.carnet_id
            }), !form.errors.carnet_id && isCompteCredit && carnets.length === 0 && form.data.client_id && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
              className: "form-text text-warning",
              children: "Le client ne poss\xE8de pas de carnet de compte actif. Veuillez d'abord cr\xE9er un carnet."
            }), selectedCarnet && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
              className: "form-text text-muted mt-2",
              children: selectedCarnet.type === 'tontine' ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
                children: ["Cat\xE9gorie : ", selectedCarnet.category || 'N/A', " \u2022 Cycles : ", selectedCarnet.nombre_cycles || 'N/A', " \u2022 Pointages : ", (_selectedCarnet$total = selectedCarnet.total_pointages) !== null && _selectedCarnet$total !== void 0 ? _selectedCarnet$total : 0, "/", (_selectedCarnet$requi = selectedCarnet.required_pointages) !== null && _selectedCarnet$requi !== void 0 ? _selectedCarnet$requi : '?']
              }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
                children: ["Compte li\xE9 : ", selectedCarnet.linked_tontine ? selectedCarnet.linked_tontine.numero : 'Aucun']
              })
            }), selectedCarnet && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
              className: "form-text text-muted mt-1",
              children: ["Assiette de l'\xE9pargne : ", (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__.formatCurrency)((_selectedCarnet$avail = selectedCarnet.available_savings) !== null && _selectedCarnet$avail !== void 0 ? _selectedCarnet$avail : 0), " \u2022 Garantie maximale possible : ", (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__.formatCurrency)((_selectedCarnet$guara = selectedCarnet.guarantee_base) !== null && _selectedCarnet$guara !== void 0 ? _selectedCarnet$guara : 0)]
            }), pointageWarning && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
              className: "form-text text-warning",
              children: "Seuil recommand\xE9 non atteint, mais l\u2019admin peut enregistrer le cr\xE9dit malgr\xE9 tout."
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
            className: "col-md-6",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("label", {
              className: "form-label",
              children: "Montant demand\xE9"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("input", {
              type: "number",
              className: "form-control ".concat(form.errors.montant_demande ? 'is-invalid' : ''),
              value: form.data.montant_demande,
              onChange: function onChange(e) {
                return form.setData('montant_demande', e.target.value);
              },
              min: "1000",
              required: true
            }), form.errors.montant_demande && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
              className: "invalid-feedback",
              children: form.errors.montant_demande
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
            className: "col-md-4",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("label", {
              className: "form-label",
              children: "Type de cr\xE9dit"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("select", {
              className: "form-select",
              value: form.data.type,
              onChange: function onChange(e) {
                return form.setData('type', e.target.value);
              },
              required: true,
              disabled: isTypeFixedByCarnet,
              children: (selectedCarnet === null || selectedCarnet === void 0 ? void 0 : selectedCarnet.type) === 'compte' ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                  value: "compte",
                  children: "Cr\xE9dit sur compte"
                })
              }) : (selectedCarnet === null || selectedCarnet === void 0 ? void 0 : selectedCarnet.type) === 'tontine' ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                  value: "quinzaine",
                  children: "Cr\xE9dit quinzaine"
                })
              }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                  value: "",
                  children: "Choisir"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                  value: "compte",
                  children: "Cr\xE9dit sur compte"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                  value: "quinzaine",
                  children: "Cr\xE9dit quinzaine"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                  value: "mensuel",
                  children: "Cr\xE9dit mensuel"
                })]
              })
            }), (selectedCarnet === null || selectedCarnet === void 0 ? void 0 : selectedCarnet.type) === 'compte' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
              className: "form-text text-muted",
              children: "Le type est fix\xE9 \xE0 Cr\xE9dit sur compte pour ce carnet."
            }), (selectedCarnet === null || selectedCarnet === void 0 ? void 0 : selectedCarnet.type) === 'tontine' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
              className: "form-text text-muted",
              children: "Le type est fix\xE9 \xE0 Cr\xE9dit quinzaine pour ce carnet de tontine."
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
            className: "col-md-4",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("label", {
              className: "form-label",
              children: "Mode de calcul"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("select", {
              className: "form-select",
              value: form.data.mode,
              onChange: function onChange(e) {
                return form.setData('mode', e.target.value);
              },
              required: true,
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                value: "",
                children: "Choisir"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                value: "fixe",
                children: "Fixe"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                value: "degressif",
                children: "D\xE9gressif"
              })]
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
            className: "col-md-4",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("label", {
              className: "form-label",
              children: "P\xE9riodicit\xE9"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("select", {
              className: "form-select",
              value: form.data.periodicite,
              onChange: function onChange(e) {
                return form.setData('periodicite', e.target.value);
              },
              required: true,
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                value: "",
                children: "Choisir"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                value: "quinzaine",
                children: "Quinzaine"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("option", {
                value: "mensuelle",
                children: "Mensuelle"
              })]
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
            className: "col-md-4",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("label", {
              className: "form-label",
              children: "Nombre d'\xE9ch\xE9ances"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("input", {
              type: "number",
              className: "form-control",
              value: form.data.nombre_echeances,
              onChange: function onChange(e) {
                return form.setData('nombre_echeances', e.target.value);
              },
              min: "1",
              required: true
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
            className: "col-md-4",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("label", {
              className: "form-label",
              children: "Taux standard (%)"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("input", {
              type: "number",
              step: "0.01",
              min: "0",
              className: "form-control",
              value: form.data.taux,
              onChange: function onChange(e) {
                return form.setData('taux', e.target.value);
              },
              required: true
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
            className: "col-md-4",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("label", {
              className: "form-label",
              children: "Taux manuel (%)"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("input", {
              type: "number",
              step: "0.01",
              min: "0",
              className: "form-control",
              value: form.data.taux_manuelle,
              onChange: function onChange(e) {
                return form.setData('taux_manuelle', e.target.value);
              },
              placeholder: "Optionnel"
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
            className: "col-md-12",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("label", {
              className: "form-label",
              children: "Date de d\xE9but"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("input", {
              type: "date",
              className: "form-control ".concat(form.errors.date_debut ? 'is-invalid' : ''),
              value: form.data.date_debut,
              onChange: function onChange(e) {
                return form.setData('date_debut', e.target.value);
              },
              min: today,
              required: true
            }), form.errors.date_debut && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
              className: "invalid-feedback",
              children: form.errors.date_debut
            })]
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
            className: "col-12",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
              className: "row gy-3",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                className: "col-md-4",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
                  className: "border rounded-3 p-3 bg-light",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                    className: "text-muted",
                    children: "Montant total"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                    className: "fs-4 fw-bold",
                    children: (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__.formatCurrency)(totalDue)
                  })]
                })
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                className: "col-md-4",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
                  className: "border rounded-3 p-3 bg-light",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                    className: "text-muted",
                    children: "Int\xE9r\xEAt total"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                    className: "fs-4 fw-bold",
                    children: (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__.formatCurrency)(totalInterest)
                  })]
                })
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                className: "col-md-4",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
                  className: "border rounded-3 p-3 bg-light",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                    className: "text-muted",
                    children: "\xC9ch\xE9ance moyenne"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                    className: "fs-4 fw-bold",
                    children: (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__.formatCurrency)(meanInstallment)
                  })]
                })
              })]
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
            className: "col-12",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
              className: "card border-secondary",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                className: "card-header bg-white",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("strong", {
                  children: "Aper\xE7u des \xE9ch\xE9ances"
                })
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                className: "card-body p-0",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("table", {
                  className: "table table-sm mb-0",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("thead", {
                    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("tr", {
                      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("th", {
                        children: "#"
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("th", {
                        children: "Date"
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("th", {
                        children: "Principal"
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("th", {
                        children: "Int\xE9r\xEAts"
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("th", {
                        children: "Total"
                      })]
                    })
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("tbody", {
                    children: [paginatedSchedule.map(function (item) {
                      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("tr", {
                        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("td", {
                          children: item.numero
                        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("td", {
                          children: (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__.formatDateToFR)(item.date)
                        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("td", {
                          children: (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__.formatCurrency)(item.principal)
                        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("td", {
                          children: (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__.formatCurrency)(item.interest)
                        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("td", {
                          children: (0,_Utils_creditHelpers__WEBPACK_IMPORTED_MODULE_3__.formatCurrency)(item.total)
                        })]
                      }, item.numero);
                    }), schedule.length === 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("tr", {
                      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("td", {
                        colSpan: "5",
                        className: "text-center py-4",
                        children: "Remplissez le formulaire pour afficher le plan."
                      })
                    })]
                  })]
                })
              }), schedule.length > pageSize && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
                className: "card-footer bg-white border-top",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("nav", {
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("ul", {
                    className: "pagination justify-content-center mb-0",
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("li", {
                      className: "page-item ".concat(currentPage === 1 ? 'disabled' : ''),
                      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("button", {
                        type: "button",
                        className: "page-link",
                        onClick: function onClick() {
                          return setCurrentPage(function (prev) {
                            return Math.max(prev - 1, 1);
                          });
                        },
                        children: "Pr\xE9c\xE9dent"
                      })
                    }), Array.from({
                      length: pageCount
                    }, function (_, index) {
                      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("li", {
                        className: "page-item ".concat(currentPage === index + 1 ? 'active' : ''),
                        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("button", {
                          type: "button",
                          className: "page-link",
                          onClick: function onClick() {
                            return setCurrentPage(index + 1);
                          },
                          children: index + 1
                        })
                      }, index);
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("li", {
                      className: "page-item ".concat(currentPage === pageCount ? 'disabled' : ''),
                      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("button", {
                        type: "button",
                        className: "page-link",
                        onClick: function onClick() {
                          return setCurrentPage(function (prev) {
                            return Math.min(prev + 1, pageCount);
                          });
                        },
                        children: "Suivant"
                      })
                    })]
                  })
                })
              })]
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
            className: "col-12 text-end",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("button", {
              type: "submit",
              className: "btn btn-primary",
              children: "Enregistrer la demande"
            })
          })]
        })
      })]
    })
  });
}

/***/ }),

/***/ "./resources/js/Utils/creditHelpers.js":
/*!*********************************************!*\
  !*** ./resources/js/Utils/creditHelpers.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "formatCurrency": () => (/* binding */ formatCurrency),
/* harmony export */   "parseDateString": () => (/* binding */ parseDateString),
/* harmony export */   "formatDateToFR": () => (/* binding */ formatDateToFR),
/* harmony export */   "periodDays": () => (/* binding */ periodDays),
/* harmony export */   "calculateRate": () => (/* binding */ calculateRate),
/* harmony export */   "buildScheduleFromForm": () => (/* binding */ buildScheduleFromForm)
/* harmony export */ });
function formatCurrency(value) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    maximumFractionDigits: 0
  }).format(value || 0);
}
function parseDateString(value) {
  if (!value) {
    return new Date(NaN);
  }
  if (value instanceof Date) {
    return value;
  }
  var stringValue = String(value).trim();
  var isoDate = stringValue.replace(' ', 'T');
  var parsedIso = new Date(isoDate);
  if (!Number.isNaN(parsedIso.getTime())) {
    return parsedIso;
  }
  var parts = stringValue.split(/[-T:\s]/).map(Number).filter(function (part) {
    return !Number.isNaN(part);
  });
  if (parts.length >= 3) {
    return new Date(parts[0], parts[1] - 1, parts[2]);
  }
  return new Date(NaN);
}
function formatDateToFR(value) {
  if (!value) return '';
  var date = typeof value === 'string' ? parseDateString(value) : value;
  if (date instanceof Date && Number.isNaN(date.getTime())) {
    return '';
  }
  return new Intl.DateTimeFormat('fr-FR').format(date);
}
function periodDays(periodicite) {
  return periodicite === 'quinzaine' ? 15 : 30;
}
function calculateRate(taux, tauxManuel) {
  var base = Number(taux) || 0;
  var manual = tauxManuel !== null && tauxManuel !== undefined && tauxManuel !== '' ? Number(tauxManuel) : null;
  return manual > 0 ? manual : base;
}
function buildScheduleFromForm(form) {
  var montant = Number(form.montant_demande || 0);
  var taux = calculateRate(form.taux, form.taux_manuelle) / 100;
  var nombre = Math.max(1, Number(form.nombre_echeances || 1));
  var mode = form.mode || 'fixe';
  var periodDaysCount = periodDays(form.periodicite || 'mensuelle');
  var start = form.date_debut || new Date().toISOString().slice(0, 10);
  var startDate = parseDateString(start);
  var principalBase = Math.round(montant / nombre * 100) / 100;
  var remaining = montant;
  var schedule = [];
  for (var i = 1; i <= nombre; i += 1) {
    var interest = mode === 'degressif' ? Math.round(remaining * taux * 100) / 100 : Math.round(montant * taux * 100) / 100;
    var principal = i === nombre ? Math.round(remaining * 100) / 100 : principalBase;
    var total = Math.round((principal + interest) * 100) / 100;
    var dueDate = new Date(startDate);
    dueDate.setDate(dueDate.getDate() + (i - 1) * periodDaysCount);
    schedule.push({
      numero: i,
      date: dueDate.toISOString().slice(0, 10),
      principal: principal,
      interest: interest,
      total: total
    });
    remaining = Math.round((remaining - principal) * 100) / 100;
  }
  return schedule;
}

/***/ })

}]);