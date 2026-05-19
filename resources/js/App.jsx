import { BrowserRouter } from "react-router-dom";
import { ToastProvider } from "./components/common/ToastProvider.jsx";
import AppRoutes from "./routes/AppRoutes.jsx";

function App() {
    return (
        <BrowserRouter>
            <ToastProvider>
                <AppRoutes />
            </ToastProvider>
        </BrowserRouter>
    );
}

export default App;
