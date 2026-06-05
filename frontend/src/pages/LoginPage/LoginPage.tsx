import { useLocation } from 'react-router-dom';
import { LoginForm } from '../../features/auth/ui/LoginForm';

export const LoginPage = () => {
  const location = useLocation();
  const registered = location.state?.registered;

  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-slate-950 p-4">
      {registered && (
        <div className="mb-6 w-full max-w-md p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm rounded-xl text-center shadow-lg shadow-emerald-500/5">
          Registration successful! Please log in with your credentials.
        </div>
      )}
      <LoginForm />
    </div>
  );
};
