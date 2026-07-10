/**
 * CommerceFlow admin SPA entry point.
 *
 * @package
 */

/**
 * WordPress dependencies
 */
import { render } from "@wordpress/element";
import domReady from "@wordpress/dom-ready";
import { HashRouter, Route, Routes, Navigate } from "react-router-dom";

/**
 * Internal dependencies
 */
import { AdminPage } from "./components/AdminPage";
import { DashboardPage } from "./pages/DashboardPage";
import { SettingsPage } from "./pages/SettingsPage";
import { AutomationPage } from "./pages/AutomationPage";

domReady(() => {
  const root = document.getElementById("commerceflow-root");
  if (!root) {
    return;
  }

  render(
    <HashRouter>
      <AdminPage>
        <Routes>
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          <Route path="/dashboard" element={<DashboardPage />} />
          <Route path="/automation" element={<AutomationPage />} />
          <Route path="/settings" element={<SettingsPage />} />
        </Routes>
      </AdminPage>
    </HashRouter>,
    root,
  );
});
