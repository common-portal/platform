<?php

namespace App\Http\Middleware;

use App\Models\TenantAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSubdomainTenant
{
    /**
     * Handle subdomain-based tenant resolution.
     * 
     * If a subdomain matches a tenant's whitelabel_subdomain_slug,
     * store the tenant in the request for branding overrides.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain) {
            $tenant = TenantAccount::where('whitelabel_subdomain_slug', $subdomain)
                ->where('is_soft_deleted', false)
                ->first();

            if ($tenant) {
                // Store tenant in request for use in views/controllers
                $request->attributes->set('subdomain_tenant', $tenant);
                
                // Also store in session for convenience
                session(['subdomain_tenant_id' => $tenant->id]);
            }
        } else {
            // Clear any previous subdomain tenant
            session()->forget('subdomain_tenant_id');
        }

        return $next($request);
    }

    /**
     * Extract subdomain from host.
     * 
     * Examples:
     * - client.commonportal.com → client
     * - www.commonportal.com → null (www is ignored)
     * - commonportal.com → null
     * - localhost → null
     */
    protected function extractSubdomain(string $host): ?string
    {
        // Skip localhost and IP addresses
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        $parts = explode('.', $host);

        // Need at least 3 parts for subdomain (sub.domain.tld)
        if (count($parts) < 3) {
            return null;
        }

        $subdomain = $parts[0];

        // Ignore common non-tenant subdomains
        $ignored = ['www', 'api', 'admin', 'mail', 'smtp', 'ftp'];
        if (in_array(strtolower($subdomain), $ignored)) {
            return null;
        }

        return strtolower($subdomain);
    }
}
