import { useActionState, startTransition } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { authApi } from '../../../shared/api/authApi';
import type { ActionState } from '../../../shared/types/action';

export const RegisterForm = () => {
  const navigate = useNavigate();

  const [state, formAction, isPending] = useActionState(
    async (_prevState: ActionState, formData: FormData): Promise<ActionState> => {
      const email = formData.get('email') as string;
      const password = formData.get('password') as string;
      const confirmPassword = formData.get('confirmPassword') as string;

      if (!email || !password || !confirmPassword) {
        return { error: 'Please fill in all fields.', success: false };
      }

      if (password !== confirmPassword) {
        return { error: 'Passwords do not match.', success: false };
      }

      if (password.length < 8) {
        return { error: 'Password must be at least 8 characters long.', success: false };
      }

      try {
        await authApi.register({ email, password });
        
        startTransition(() => {
          navigate('/login', { state: { registered: true } });
        });
        
        return { error: null, success: true };
      } catch (err: any) { // eslint-disable-line @typescript-eslint/no-explicit-any
        const message = err.response?.data?.detail || err.response?.data?.message || 'Registration failed. Please try again.';
        return { error: message, success: false };
      }
    },
    { error: null, success: false }
  );

  return (
    <div className="w-full max-w-md p-8 bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-2xl">
      <div className="text-center mb-8">
        <h2 className="text-3xl font-bold tracking-tight text-white mb-2">Create Account</h2>
        <p className="text-sm text-slate-400">Join DriveAgency to start subscribing to cars</p>
      </div>

      <form action={formAction} className="space-y-6">
        <div className="space-y-2">
          <label htmlFor="email" className="block text-sm font-medium text-slate-300">
            Email Address
          </label>
          <input
            id="email"
            name="email"
            type="email"
            required
            autoComplete="email"
            placeholder="you@example.com"
            className="w-full px-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition-all"
          />
        </div>

        <div className="space-y-2">
          <label htmlFor="password" className="block text-sm font-medium text-slate-300">
            Password
          </label>
          <input
            id="password"
            name="password"
            type="password"
            required
            autoComplete="new-password"
            placeholder="Min. 8 characters"
            className="w-full px-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition-all"
          />
        </div>

        <div className="space-y-2">
          <label htmlFor="confirmPassword" className="block text-sm font-medium text-slate-300">
            Confirm Password
          </label>
          <input
            id="confirmPassword"
            name="confirmPassword"
            type="password"
            required
            autoComplete="new-password"
            placeholder="Repeat password"
            className="w-full px-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition-all"
          />
        </div>

        {state.error && (
          <div className="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm rounded-xl">
            {state.error}
          </div>
        )}

        <button
          type="submit"
          disabled={isPending}
          className="w-full py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isPending ? 'Registering...' : 'Register'}
        </button>
      </form>

      <div className="mt-8 text-center text-sm text-slate-400">
        Already have an account?{' '}
        <Link to="/login" className="font-medium text-violet-400 hover:text-violet-300 transition-colors">
          Sign in here
        </Link>
      </div>
    </div>
  );
};
