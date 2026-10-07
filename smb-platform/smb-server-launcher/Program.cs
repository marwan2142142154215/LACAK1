using System;
using System.Diagnostics;
using System.Threading.Tasks;

namespace SmbServerLauncher;

class Program
{
    static int Main(string[] args)
    {
        Console.WriteLine("SMB Server Launcher");
        if (args.Length == 0)
        {
            Console.WriteLine("Usage: SmbServerLauncher <start|stop|status|doctor>");
            return 0;
        }

        switch (args[0].ToLowerInvariant())
        {
            case "start":
                StartIfNotRunning("PostgreSQL", "docker", "start apk-postgres-1");
                StartIfNotRunning("Redis", "docker", "start apk-redis-1 smb-redis");
                StartProcess("Laravel API", "php", "artisan serve --port=8100", @"C:\Users\ACE COMPUTER\Documents\apk opencode\smb-platform\smb-api");
                StartProcess("AdonisJS Gateway", "node", "ace.js serve --hmr", @"C:\Users\ACE COMPUTER\Documents\apk opencode\smb-platform\smb-gateway");
                StartProcess("Cloudflared Tunnel", "cloudflared", "tunnel run", null);
                Console.WriteLine("SYSTEM STARTING");
                return 0;
            case "stop":
                StopProcessByName("php");
                StopProcessByName("node");
                StopProcessByName("cloudflared");
                Console.WriteLine("SYSTEM STOPPED");
                return 0;
            case "status":
                CheckPort("Laravel API", 8100);
                CheckPort("AdonisJS HTTP", 3333);
                CheckPort("WebSocket", 8080);
                CheckPort("PostgreSQL", 5432);
                CheckPort("Redis", 6379);
                Console.WriteLine(File.Exists(@"C:\Program Files (x86)\cloudflared\cloudflared.exe") ? "[OK] cloudflared binary present" : "[FAIL] cloudflared not installed");
                return 0;
            case "doctor":
                Console.WriteLine(RunDoctor());
                return 0;
            default:
                Console.WriteLine("Unknown command: " + args[0]);
                return 1;
        }
    }

    static void StartIfNotRunning(string name, string file, string arguments)
    {
        try
        {
            Process.Start(new ProcessStartInfo(file, arguments) { UseShellExecute = false, CreateNoWindow = true });
            Console.WriteLine($"[START] {name}");
        }
        catch (Exception ex)
        {
            Console.WriteLine($"[FAIL] {name}: {ex.Message}");
        }
    }

    static void StartProcess(string name, string file, string arguments, string? cwd)
    {
        try
        {
            var psi = new ProcessStartInfo(file, arguments) { UseShellExecute = false };
            if (cwd != null) psi.WorkingDirectory = cwd;
            Process.Start(psi);
            Console.WriteLine($"[START] {name}");
        }
        catch (Exception ex)
        {
            Console.WriteLine($"[FAIL] {name}: {ex.Message}");
        }
    }

    static void StopProcessByName(string name)
    {
        foreach (var p in Process.GetProcessesByName(name))
        {
            try { p.Kill(true); } catch { }
        }
        Console.WriteLine($"[STOP] {name}");
    }

    static void CheckPort(string name, int port)
    {
        try
        {
            using var client = new System.Net.Sockets.TcpClient();
            var result = client.BeginConnect("127.0.0.1", port, null, null);
            var ok = result.AsyncWaitHandle.WaitOne(TimeSpan.FromSeconds(2));
            Console.WriteLine(ok && client.Connected ? $"[OK] {name} :{port}" : $"[FAIL] {name} :{port}");
        }
        catch
        {
            Console.WriteLine($"[FAIL] {name} :{port}");
        }
    }

    static string RunDoctor()
    {
        return "Gunakan: php artisan smb:doctor di direktori smb-api untuk cek lengkap semua service.";
    }
}
