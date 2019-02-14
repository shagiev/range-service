package http;

import com.sun.net.httpserver.HttpExchange;

import java.io.IOException;

public class HttpHandler implements com.sun.net.httpserver.HttpHandler {
    @Override
    public void handle(HttpExchange httpExchange) throws IOException {
        System.out.println(httpExchange.getRequestBody());
    }
}
