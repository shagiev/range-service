package http;

import com.sun.net.httpserver.HttpContext;
import com.sun.net.httpserver.HttpServer;

import java.io.IOException;
import java.net.InetSocketAddress;
import java.util.logging.Logger;

public class Server {
    Logger logger = Logger.getLogger("http.Server");

    public static void main(String[] args) throws IOException {
        HttpServer server = HttpServer.create();
        server.bind(new InetSocketAddress(8700), 0);
        HttpContext context = server.createContext("/", new HttpHandler());
        server.start();

        System.out.println("Server started on :8700");
    }
}
